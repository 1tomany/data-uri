<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\Record\DataUriInterface;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use OneToMany\DataUri\Record\DataUri;
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
use function implode;
use function in_array;
use function is_dir;
use function is_file;
use function is_readable;
use function is_string;
use function is_writable;
use function parse_url;
use function rtrim;
use function sprintf;
use function stream_get_contents;
use function stream_get_wrappers;
use function stripos;
use function strlen;
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

    public function decode(mixed $data, ?string $name = null): DataUriInterface
    {
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

        // Generate a random file name
        $tempName = $this->randomString(12);

        // Resolve the display name
        $displayName = trim($name ?? '');

        // Use the file as the display name
        if ($dataIsFile && !$displayName) {
            $displayName = $data;
        }

        // Use the path component from the URL as the display name
        if (!$dataIsFile && 0 === stripos($data, 'http')) {
            $displayName = parse_url($data)['path'] ?? '';
        }

        try {
            /** @var non-empty-string $tempPath */
            $tempPath = Path::join($this->tempDir, $tempName);
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Generating the temporary path failed: %s.', rtrim($e->getMessage(), '.')), previous: $e);
        }

        if ($dataIsFile) {
            try {
                // Copy the data to the temporary file
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

        // Attempt to determine the file type
        $type = Type::createFromPath($tempPath);

        if ($extension = $type->getExtension()) {
            try {
                /** @var non-empty-string $tempName */
                $tempName = Path::changeExtension($tempName, $extension);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Generating a temporary path failed: %s.', rtrim($e->getMessage(), '.')), previous: $e);
            }

            /** @var non-empty-string $path */
            $path = Path::join(Path::getDirectory($tempPath), $tempName);

            try {
                // Rename the temporary file with an extension
                $this->filesystem->rename($tempPath, $path, true);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Renaming "%s" to "%s" failed.', $tempPath, $path), previous: $e);
            }
        } else {
            $path = $tempPath;
        }

        /** @var non-empty-string $displayName */
        $displayName = basename($displayName ?: $path);

        if (!$hash = hash_file('sha256', $path)) {
            throw new RuntimeException(sprintf('Calculating the hash of the file "%s" failed.', $path));
        }

        // Validate minimum hash length
        if (strlen($hash) < DataUriInterface::MINIMUM_HASH_LENGTH) {
            throw new RuntimeException(sprintf('The hash "%s" must be %d or more characters.', $hash, DataUriInterface::MINIMUM_HASH_LENGTH));
        }

        // Generate the URI as a combination of the hash and random name
        $uri = implode('/', [$hash[0].$hash[1], $hash[2].$hash[3], $tempName]);

        if (false === $size = @filesize($path)) {
            throw new RuntimeException(sprintf('Reading the size of the file "%s" failed.', $path));
        }

        return new DataUri($hash, $path, $displayName, $size, $type, $uri);
    }

    public function decodeBase64(string $data, string $format, ?string $name = null): DataUriInterface
    {
        return $this->decode(sprintf('data:%s;base64,%s', $format, $data), $name);
    }

    public function decodeText(string $text, ?string $name = null): DataUriInterface
    {
        try {
            $type = Type::Txt;

            // Generate a random name if needed
            if (empty($name = trim($name ?? ''))) {
                $name = $this->randomString(12);
            }

            // Append the .txt extension if needed
            if (!Path::hasExtension($name, $type->getExtension(), true)) {
                $name = sprintf('%s.%s', $name, $type->getExtension());
            }
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Generating a temporary name failed: %s.', rtrim($e->getMessage(), '.')), previous: $e);
        }

        return $this->decodeBase64(base64_encode($text), Type::Txt->getFormat(), $name);
    }

    /**
     * @param positive-int $length
     *
     * @return non-empty-string
     */
    private function randomString(int $length): string
    {
        try {
            /** @var non-empty-string $randomString */
            $randomString = new Randomizer()->getBytesFromString('1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', $length);
        } catch (RandomException|RandomError $e) {
            throw new RuntimeException('Generating a sufficiently random string failed.', previous: $e);
        }

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
