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
use function is_dir;
use function is_file;
use function is_link;
use function is_readable;
use function is_string;
use function is_writable;
use function mkdir;
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
    private readonly string $tempDir;

    private const string LIBRARY_DIRECTORY = '1tomany-data-uri';

    public function __construct(
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        $this->tempDir = sys_get_temp_dir();

        if (!is_writable($this->tempDir)) {
            throw new InvalidArgumentException(sprintf('The temp dir "%s" is not writable.', $this->tempDir));
        }
    }

    public function decode(
        mixed $data,
        ?string $name = null,
        string|Type|null $type = null,
    ): TemporaryFileInterface {
        if (!is_string($data) && !$data instanceof \Stringable) {
            throw new InvalidArgumentException('The data must be a non-NULL string or implement the "\Stringable" interface.');
        }

        if (is_empty($data = trim($data), false)) {
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

        // Generate a random file name
        $tempName = FilenameHelper::generate(12);

        // Determine the display name
        $displayName = trim((string) $name);

        // Use the file path for the name
        if (!$displayName && $dataIsFile) {
            $displayName = basename($data);
        }

        // Use the URL path for the name
        if (!$displayName && $dataIsUrl) {
            $urlBits = parse_url($data);

            if (isset($urlBits['path'])) {
                $displayName = $urlBits['path'];
            }

            $displayName = basename($displayName);
        }

        $ownedDirectory = $this->createOwnedDirectory();
        $tempPath = $path = null;

        try {
            try {
                /** @var non-empty-string $tempPath */
                $tempPath = Path::join($ownedDirectory, self::LIBRARY_DIRECTORY, $tempName);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Generating the temporary path failed: %s.', rtrim($e->getMessage(), '.')), previous: $e);
            }

            $path = $tempPath;

            if ($dataIsFile) {
                try {
                    // Copy the data to the temporary file
                    $this->filesystem->copy($data, $tempPath, true);
                } catch (FilesystemExceptionInterface $e) {
                    throw new RuntimeException(sprintf('Copying "%s" to "%s" failed.', $data, $tempPath), previous: $e);
                }
            } else {
                // Ensure data, file, http, and https streams are registered
                $this->assertStreamsAreRegistered(['data', 'file', 'http', 'https']);

                // Read, decode, and stream the data
                if (!$stream = @fopen($data, 'rb')) {
                    throw new InvalidArgumentException('Decoding the data stream failed.');
                }

                if (false === $contents = stream_get_contents($stream)) {
                    throw new RuntimeException('Reading the stream contents failed.');
                }

                try {
                    // Write the streamed data to the temporary file
                    $this->filesystem->dumpFile($tempPath, $contents);
                } catch (FilesystemExceptionInterface $e) {
                    throw new RuntimeException(sprintf('Writing the data to the file "%s" failed.', $tempPath), previous: $e);
                }
            }

            // Determine the file type
            if ($type && is_string($type)) {
                $type = Type::create($type);
            }

            if (!$type instanceof Type) {
                $type = Type::createFromPath(...[
                    'path' => $tempPath,
                ]);
            }

            if ($extension = $type->getExtension()) {
                try {
                    /** @var non-empty-string $tempName */
                    $tempName = Path::changeExtension($tempName, $extension);
                } catch (FilesystemExceptionInterface $e) {
                    throw new RuntimeException(sprintf('Generating a temporary path failed: %s.', rtrim($e->getMessage(), '.')), previous: $e);
                }

                /** @var non-empty-string $path */
                $path = Path::join($ownedDirectory, $tempName);

                try {
                    // Rename the temporary file with an extension
                    $this->filesystem->rename($tempPath, $path, true);
                } catch (FilesystemExceptionInterface $e) {
                    throw new RuntimeException(sprintf('Renaming "%s" to "%s" failed.', $tempPath, $path), previous: $e);
                }
            }

            /** @var non-empty-string $displayName */
            $displayName = basename($displayName ?: $path);

            // Ensure the filesize can be calculated
            if (false === $size = @filesize($path)) {
                throw new RuntimeException(sprintf('Reading the size of the file "%s" failed.', $path));
            }

            return new TemporaryFile($path, $ownedDirectory, $displayName, $size, $type);
        } catch (\Throwable $e) {
            $this->rollback($ownedDirectory, $tempPath, $path);

            throw $e;
        }
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
     * @throws RuntimeException when one or more streams are not registered with PHP
     */
    private function assertStreamsAreRegistered(array $streams): void
    {
        if ([] !== $missingStreams = array_diff($streams, stream_get_wrappers())) {
            throw new RuntimeException(sprintf('The following streams are not registered in this environment: "%s".', implode('", "', $missingStreams)));
        }
    }

    /**
     * @return non-empty-string
     */
    private function createOwnedDirectory(): string
    {
        for ($attempt = 0; $attempt < 10; ++$attempt) {
            $ownedDirectory = Path::join($this->tempDir, FilenameHelper::generate(20));

            if (@mkdir($ownedDirectory, 0700)) {
                return $ownedDirectory;
            }
        }

        throw new RuntimeException('Creating a unique temporary directory failed.');
    }

    private function rollback(string $ownedDirectory, ?string ...$paths): void
    {
        $ownedDirectory = Path::canonicalize($ownedDirectory);

        if (Path::canonicalize($this->tempDir) !== dirname($ownedDirectory)) {
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
