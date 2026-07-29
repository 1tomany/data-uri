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
        $displayName = trim((string) $name);

        // Basename of the file as the display name
        if ('' === $displayName && $dataIsFile) {
            $displayName = basename($data);
        }

        // Basename of the URL as the display name
        if ('' === $displayName && $dataIsUrl) {
            if (is_array($urlBits = parse_url($data))) {
                if (true === isset($urlBits['path'])) {
                    $displayName = $urlBits['path'];
                }
            }
        }

        $displayName = FilenameHelper::sanitize(...[
            'filename' => trim($displayName),
        ]);

        // Generate a base directory if a valid
        // display name is used to avoid clashes
        if (false === is_empty($displayName, false)) {
            $_base = FilenameHelper::generate(6);
        }

        // Generate a random display name
        if (true === is_empty($displayName, false)) {
            $displayName = FilenameHelper::generate(12);
        }

        try {
            $_path = Path::join($this->rootDirectory, self::FILE_DIRECTORY, $_base ?? '', $displayName);
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException('Generating the temporary path failed.', previous: $e);
        } finally {
            if (!isset($_base)) {
                $_base = null;
            }
        }

        if ('' === $_path) {
            throw new RuntimeException('An empty file path was generated.');
        }

        // Generate an absolute base directory
        if (false === is_empty($_base, false)) {
            $_base = Path::getDirectory($_path);
        }

        if ($dataIsFile || $dataIsUrl) {
            $this->assertStreamsAreRegistered(['http', 'https']);

            try {
                // Copy the source data to the temporary file
                $this->filesystem->copy($data, $_path, true);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Copying the %s "%s" to "%s" failed.', $dataIsUrl ? 'URL' : 'file', $data, $_path), previous: $e);
            }
        } else {
            $this->assertStreamsAreRegistered(['data', 'file']);

            // Read, decode, and stream the data
            if (!$stream = @fopen($data, 'rb')) {
                throw new RuntimeException('Opening a stream to decode the data failed.');
            }

            if (false === $contents = stream_get_contents($stream)) {
                throw new RuntimeException('Reading the data from the stream failed.');
            }

            try {
                // Write the streamed data to the temporary file
                $this->filesystem->dumpFile($_path, $contents);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Writing the data to the file "%s" failed.', $_path), previous: $e);
            }
        }

        if (null !== $type) {
            $_type = Type::create($type);
        } else {
            $_type = Type::createFromPath(...[
                'path' => $_path,
            ]);
        }

        if ($extension = $_type->getExtension()) {
            $dest = FilenameHelper::changeExtension(
                $_path, $extension, lowercase: true,
            );

            try {
                // Rename the temporary file with an extension
                $this->filesystem->rename($_path, $dest, true);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Renaming the file "%s" to "%s" failed.', $_path, $dest), previous: $e);
            }

            $_path = $dest;
        }

        // Ensure the filesize can be calculated
        if (false === $_size = @filesize($_path)) {
            throw new RuntimeException(sprintf('Reading the size of the file "%s" failed.', $_path));
        }

        return new TemporaryFile($_path, $_base, $displayName, $_size, $_type);
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
