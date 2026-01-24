<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use Random\RandomException;
use Symfony\Component\Filesystem\Exception\ExceptionInterface as FilesystemExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function bin2hex;
use function ctype_print;
use function fopen;
use function in_array;
use function is_dir;
use function is_file;
use function is_readable;
use function is_string;
use function is_writable;
use function random_bytes;
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
    /**
     * @var non-empty-string
     */
    private readonly string $tempDirectory;

    public function __construct(
        ?string $tempDirectory = null,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        $this->tempDirectory = $this->createDirectory($tempDirectory);
    }

    public function decode(mixed $data, ?string $name, bool $selfDestruct = true): void
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

        // $dataIsHttpUrl = !$dataIsFile && (0 === \stripos($data, 'http://') || 0 === \stripos($data, 'https://'));

        try {
            /** @var non-empty-string $tempFilePath */
            $tempFilePath = Path::join($this->tempDirectory, sprintf('1tomany_datauri_%s', bin2hex(random_bytes(6))));
        } catch (FilesystemExceptionInterface|RandomException $e) {
            throw new RuntimeException(sprintf('Generating a temporary path failed: %s.', rtrim($e->getMessage(), '.')), previous: $e);
        }

        if ($dataIsFile) {
            try {
                // Copy the file to the temporary file
                $this->filesystem->copy($data, $tempFilePath, true);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Copying "%s" to "%s" failed.', $data, $tempFilePath), previous: $e);
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
                throw new RuntimeException('Reading the data stream failed.');
            }

            try {
                // Write the streamed data to the temporary file
                $this->filesystem->dumpFile($tempFilePath, $contents);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Writing the data to the file "%s" failed.', $tempFilePath), previous: $e);
            }
        }
    }

    public function decodeBase64(): void
    {
    }

    public function decodeText(): void
    {
    }

    /**
     * @return non-empty-string
     */
    private function createDirectory(?string $directory): string
    {
        $directory = trim($directory ?? '') ?: sys_get_temp_dir();

        try {
            if (is_dir($directory) && !is_writable($directory)) {
                throw new InvalidArgumentException(sprintf('The directory "%s" is not writable.', $directory));
            }

            if (!$this->filesystem->exists($directory)) {
                $this->filesystem->mkdir($directory);
            }
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Creating the directory "%s" failed.', $directory), previous: $e);
        }

        return $directory;
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
