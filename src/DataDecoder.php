<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Contract\Enum\FileType;
use OneToMany\DataUri\Contract\Record\SmartFileInterface;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use Random\RandomError;
use Random\RandomException;
use Random\Randomizer;
use Symfony\Component\Filesystem\Exception\ExceptionInterface as FilesystemExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function ctype_print;
use function filesize;
use function fopen;
use function hash_file;
use function in_array;
use function is_dir;
use function is_file;
use function is_readable;
use function is_string;
use function is_writable;
use function rtrim;
use function sprintf;
use function stream_get_contents;
use function stream_get_wrappers;
use function stripos;
use function strlen;
use function substr;
use function sys_get_temp_dir;
use function trim;

use const PHP_MAXPATHLEN;

final class DataDecoder
{
    private readonly string $tempDir;

    public function __construct(private readonly Filesystem $filesystem = new Filesystem())
    {
        $this->tempDir = sys_get_temp_dir();

        if (!is_writable($this->tempDir)) {
            throw new InvalidArgumentException(sprintf('The temp dir "%s" is not writable.', $this->tempDir));
        }
    }

    public function decode(
        mixed $data,
        ?string $name,
        bool $selfDestruct = true,
    ): SmartFileInterface {
        if (!is_string($data) && !$data instanceof \Stringable) {
            throw new InvalidArgumentException('The data must be a non-NULL string or implement the "\Stringable" interface.');
        }

        if (empty($data = trim($data))) {
            throw new InvalidArgumentException('The data cannot be empty.');
        }

        // The data is a path to a file on the local filesystem
        $dataIsFile = strlen($data) <= PHP_MAXPATHLEN && is_file($data);

        if (!$dataIsFile && is_dir($data)) {
            throw new InvalidArgumentException('The data cannot be a directory.');
        }

        if (!ctype_print($data)) {
            throw new InvalidArgumentException('The data cannot contain non-printable, control, or NULL-terminated characters.');
        }

        if ($dataIsFile && !is_readable($data)) {
            throw new InvalidArgumentException(sprintf('The file "%s" is not readable.', $data));
        }

        // $remoteKeySuffix = new Randomizer()->getBytesFromString(SmartFileInterface::REMOTE_KEY_ALPHABET, 10);
        // $dataIsHttpUrl = !$dataIsFile && (0 === \stripos($data, 'http://') || 0 === \stripos($data, 'https://'));

        try {
            /** @var non-empty-string $tempPath */
            $tempPath = Path::join($this->tempDir, $this->randomString(12));
        } catch (FilesystemExceptionInterface|RandomException $e) {
            throw new RuntimeException(sprintf('Generating the temporary path failed: %s.', rtrim($e->getMessage(), '.')), previous: $e);
        }

        if ($dataIsFile) {
            try {
                // Copy the file to the temporary file
                $this->filesystem->copy($data, $tempPath, true);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Copying "%s" to "%s" failed.', $data, $tempPath), previous: $e);
            }
        } else {
            // Ensure HTTP URLs can be streamed
            if (0 === stripos($data, 'http://')) {
                $this->assertStreamIsRegistered('http');
            }

            if (0 === stripos($data, 'https://')) {
                $this->assertStreamIsRegistered('https');
            }

            // Read, decode, and stream the data
            if (!$stream = @fopen($data, 'rb')) {
                throw new InvalidArgumentException('Opening a stream to decode the data failed.');
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

        $format = @mime_content_type($tempPath) ?: 'application/octet-stream';

        // Attempt to determine the file format
        $type = FileType::create($format);

        if (null !== $extension = $type->getExtension()) {
            try {
                /** @var non-empty-string $filePath */
                $filePath = Path::changeExtension($tempPath, $extension);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Generating a temporary path failed: %s.', rtrim($e->getMessage(), '.')), previous: $e);
            }

            try {
                $this->filesystem->rename($tempPath, $filePath, true);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Failed to rename "%s" to "%s".', $tempPath, $filePath), previous: $e);
            }
        } else {
            $filePath = $tempPath;
        }

        if (!$hash = hash_file('sha256', $filePath)) {
            throw new RuntimeException(sprintf('Calculating the hash of the file "%s" failed.', $filePath));
        }

        // Validate minimum hash length
        if (strlen($hash) < SmartFileInterface::MINIMUM_HASH_LENGTH) {
            throw new RuntimeException(sprintf('The hash "%s" must be %d or more characters.', $hash, SmartFileInterface::MINIMUM_HASH_LENGTH));
        }

        try {
            // Begin by bucketing the file into directories based on the hash
            $remoteKeyDirectory = substr($hash, 0, 2).'/'.substr($hash, 2, 2);

            // Generate a random string to use as the remote key
            // $remoteKeySuffix = new Randomizer()->getBytesFromString(SmartFileInterface::REMOTE_KEY_ALPHABET, 10);

            // Append the extension to the suffix
            // if (null !== $ext = $fileType->getExtension()) {
            //     $remoteKeySuffix = Path::changeExtension($remoteKeySuffix, $ext);
            // }

            // Append the randomly generated suffix to create the remote key
            $remoteKey = implode('/', [$remoteKeyDirectory, basename($filePath)]);
        } catch (RandomException|RandomError $e) {
            throw new RuntimeException('Generating a sufficiently random remote key failed.', previous: $e);
        }

        return new SmartFile($hash, $filePath, basename($filePath), $type->getExtension(), $type, 'text/plain', @filesize($filePath) ?: 0, $remoteKey, $selfDestruct);
    }

    public function decodeBase64(): void
    {
    }

    public function decodeText(): void
    {
    }

    /**
     * @param positive-int $length
     *
     * @return non-empty-string
     */
    private function randomString(int $length): string
    {
        /** @var non-empty-string $randomString */
        $randomString = new Randomizer()->getBytesFromString('1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', $length);

        return $randomString;
    }

    /**
     * @param non-empty-lowercase-string $stream
     *
     * @throws RuntimeException the `$stream` is not registered with PHP
     */
    private function assertStreamIsRegistered(string $stream): void
    {
        if (!in_array($stream, stream_get_wrappers())) {
            throw new RuntimeException(sprintf('The "%s" stream is not registered in this environment.', $stream));
        }
    }
}
