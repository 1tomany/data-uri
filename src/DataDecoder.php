<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\Exception\ExceptionInterface as DataUriExceptionInterface;
use OneToMany\DataUri\Contract\Record\TemporaryFileInterface;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use OneToMany\DataUri\Helper\FilenameHelper;
use OneToMany\DataUri\Record\TemporaryFile;
use Symfony\Component\Filesystem\Exception\ExceptionInterface as FilesystemExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function array_diff;
use function ctype_print;
use function dirname;
use function fclose;
use function file_exists;
use function filesize;
use function filter_var;
use function fopen;
use function implode;
use function is_array;
use function is_dir;
use function is_file;
use function is_link;
use function is_readable;
use function is_string;
use function is_writable;
use function OneToMany\IsEmpty\is_empty;
use function parse_url;
use function rmdir;
use function rtrim;
use function sprintf;
use function stream_get_contents;
use function stream_get_wrappers;
use function strlen;
use function sys_get_temp_dir;
use function trim;
use function unlink;

use const FILTER_VALIDATE_URL;
use const PHP_MAXPATHLEN;

final class DataDecoder
{
    /**
     * Directory where all temporary files are stored.
     *
     * @var non-empty-string
     */
    private readonly string $rootDirectory;

    /**
     * Sub-directory within the root directory where
     * all temporary files and directories are found.
     *
     * @var non-empty-string
     */
    private const string FILE_DIRECTORY = '1tomany-data-uri';

    public function __construct(
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        if ('' === $rootDirectory = sys_get_temp_dir()) {
            throw new RuntimeException('The root directory cannot be empty.');
        }

        if (!is_writable($rootDirectory)) {
            throw new InvalidArgumentException(sprintf('The root directory "%s" is not writable.', $rootDirectory));
        }

        if (!Path::isAbsolute($rootDirectory)) {
            throw new InvalidArgumentException(sprintf('The root directory "%s" is not an absolute path.', $rootDirectory));
        }

        $this->rootDirectory = $rootDirectory;
    }

    public function decode(
        mixed $data,
        ?string $name = null,
        string|Type|null $type = null,
    ): TemporaryFileInterface {
        if (!is_string($data) && !$data instanceof \Stringable) {
            throw new InvalidArgumentException('The data must be a non-NULL string or implement the "\Stringable" interface.');
        }

        if ('' === $data = trim($data)) {
            throw new InvalidArgumentException('The data cannot be empty.');
        }

        $dataIsUrl = $dataIsFile = false;

        if (strlen($data) <= PHP_MAXPATHLEN) {
            if (is_file($data)) {
                $dataIsFile = true;
            } else {
                $dataIsUrl = false !== filter_var($data, FILTER_VALIDATE_URL);
            }
        }

        if (!$dataIsFile && is_dir($data)) {
            throw new InvalidArgumentException('The data cannot be a directory.');
        }

        if (!ctype_print($data)) {
            throw new InvalidArgumentException('The data cannot contain non-printable, control, or NULL-terminated characters.');
        }

        if ($dataIsFile && !is_readable($data)) {
            throw new InvalidArgumentException(sprintf('The file "%s" is not readable.', $data));
        }

        // Determine the display name
        $temporaryName = trim((string) $name);

        // Basename of the file as the file name
        if ('' === $temporaryName && $dataIsFile) {
            $temporaryName = basename($data);
        }

        // Use the basename of the path component
        // from the URL to generate the file name
        if ('' === $temporaryName && $dataIsUrl) {
            if (is_array($urlBits = parse_url($data))) {
                $temporaryName = $urlBits['path'] ?? '';
            }
        }

        $temporaryName = FilenameHelper::sanitize(...[
            'filename' => trim($temporaryName),
        ]);

        // Generate a base directory if a file name was
        // generated to avoid collisions with other files
        if (false === is_empty($temporaryName, false)) {
            $temporaryBase = FilenameHelper::generate(6);
        }

        // Generate a file name if one was not found
        if (true === is_empty($temporaryName, false)) {
            $temporaryName = FilenameHelper::generate(12);
        }

        try {
            $originPath = Path::join($this->rootDirectory, self::FILE_DIRECTORY, $temporaryBase ?? '', $temporaryName);
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException('Generating the temporary path failed.', previous: $e);
        } finally {
            if (!isset($temporaryBase)) {
                $temporaryBase = null;
            }
        }

        if ('' === $originPath) {
            throw new RuntimeException('An empty file path was generated.');
        }

        // Ensure the base directory is an absolute path
        if (false === is_empty($temporaryBase, false)) {
            $temporaryBase = Path::getDirectory(...[
                'path' => trim($originPath),
            ]);
        }

        if ($dataIsFile || $dataIsUrl) {
            $this->assertStreamsAreRegistered(['http', 'https']);

            try {
                // Copy the source data to the temporary path
                $this->filesystem->copy($data, $originPath, true);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Copying the %s "%s" to "%s" failed.', $dataIsUrl ? 'URL' : 'file', $data, $originPath), previous: $e);
            }
        } else {
            $this->assertStreamsAreRegistered(['data', 'file']);

            // Read, decode, and stream the data
            if (!$stream = @fopen($data, 'rb')) {
                throw new RuntimeException('Opening a stream to decode the data failed.');
            }

            try {
                // Attempt to read the contents of the stream into memory
                if (false === $contents = stream_get_contents($stream)) {
                    throw new RuntimeException('Reading the contents of the stream failed.');
                }

                // Write the contents of the stream to a temporary file
                $this->filesystem->dumpFile($originPath, $contents);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Writing the contents of the stream to the file "%s" failed.', $originPath), previous: $e);
            } finally {
                @fclose($stream);
            }
        }

        if (is_string($type) || $type instanceof Type) {
            $temporaryType = Type::create(type: $type);
        } else {
            $temporaryType = Type::createFromPath(...[
                'path' => trim($originPath),
            ]);
        }

        if ($extension = $temporaryType->getExtension()) {
            $temporaryPath = Path::changeExtension(
                $originPath, extension: $extension,
            );

            // if (Path::hasExtension($originPath, $extension, true)) {
            //     $temporaryPath = Path::changeExtension($originPath, $extension);
            // } else {
            //     $filename = sprintf('%s.%s', $filename, $extension);
            // }

            try {
                // $temporaryPath = FilenameHelper::changeExtension(
                //     $originPath, $extension, lowercase: true,
                // );

                $this->filesystem->rename($originPath, $temporaryPath, true);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Changing the extension of the file "%s" to "%s" failed.', $temporaryBase, $extension), previous: $e);
            }
        } else {
            $temporaryPath = $originPath;
        }

        // Attempt to calculate the filesize of the file
        if (false === $temporarySize = @filesize($temporaryPath)) {
            throw new RuntimeException(sprintf('Reading the size of the file "%s" failed.', $temporaryPath));
        }

        return new TemporaryFile($temporaryPath, $temporaryBase, basename($temporaryPath), $temporarySize, $temporaryType);
    }

    public function decodeBase64(
        string $data,
        string|Type $format,
        ?string $name = null,
    ): TemporaryFileInterface {
        return $this->decode(sprintf('data:%s;base64,%s', $format instanceof Type ? $format->getFormat() : $format, $data), $name, $format);
    }

    public function decodeText(
        string $text,
        string|Type $type = Type::Txt,
        ?string $name = null,
    ): TemporaryFileInterface {
        if (!$type instanceof Type) {
            $type = Type::create($type);
        }

        if (!$type->isText()) {
            throw new InvalidArgumentException(sprintf('The type "%s" is not text.', $type->getName()));
        }

        if (null !== $name) {
            $name = trim($name);
        }

        try {
            $name = FilenameHelper::changeExtension($name ?: FilenameHelper::generate(12), $type->getExtension());
        } catch (DataUriExceptionInterface $e) {
            throw new RuntimeException(sprintf('Generating a temporary filename failed: %s.', rtrim($e->getMessage(), '.')), previous: $e);
        }

        return $this->decodeBase64(base64_encode($text), $type, $name);
    }

    /**
     * @param non-empty-list<non-empty-lowercase-string> $streams
     *
     * @throws RuntimeException when one or more streams are not registered
     */
    private function assertStreamsAreRegistered(array $streams): void
    {
        if ([] !== $missingStreams = array_diff($streams, stream_get_wrappers())) {
            throw new RuntimeException(sprintf('The following streams are not registered in this environment: "%s".', implode('", "', $missingStreams)));
        }
    }

    private function rollback(string $ownedDirectory, ?string ...$paths): void
    {
        $ownedDirectory = Path::canonicalize($ownedDirectory);

        if (Path::canonicalize($this->rootDirectory) !== dirname($ownedDirectory)) {
            return;
        }

        foreach ($paths as $path) {
            if (null === $path || $ownedDirectory !== dirname(Path::canonicalize($path))) {
                continue;
            }

            if ((file_exists($path) || is_link($path)) && (!is_dir($path) || is_link($path))) {
                @unlink($path);
            }
        }

        @rmdir($ownedDirectory);
    }
}
