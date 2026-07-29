<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\MediaTypeResolverInterface;
use OneToMany\DataUri\Contract\TemporaryFileFactoryInterface;
use OneToMany\DataUri\Contract\TemporaryFileInterface;
use OneToMany\DataUri\Exception\FileTooLargeException;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use OneToMany\DataUri\Helper\FilenameHelper;
use Symfony\Component\Filesystem\Exception\ExceptionInterface as FilesystemExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function basename;
use function chmod;
use function dirname;
use function fclose;
use function fflush;
use function filesize;
use function fopen;
use function fwrite;
use function is_dir;
use function is_link;
use function is_resource;
use function is_writable;
use function mkdir;
use function rewind;
use function sprintf;
use function stream_copy_to_stream;
use function strlen;
use function substr;
use function sys_get_temp_dir;
use function trim;

final readonly class TemporaryFileFactory implements TemporaryFileFactoryInterface
{
    public const string LIBRARY_DIRECTORY = '1tomany-data-uri';

    public const int DEFAULT_MAXIMUM_BYTES = 104_857_600;

    private string $baseDirectory;

    public function __construct(
        private Filesystem $filesystem = new Filesystem(),
        private MediaTypeResolverInterface $mediaTypeResolver = new MediaTypeResolver(),
        ?string $temporaryDirectory = null,
        private int $maximumBytes = self::DEFAULT_MAXIMUM_BYTES,
    ) {
        if ($this->maximumBytes < 1) {
            throw new InvalidArgumentException('The maximum number of bytes must be greater than zero.');
        }

        $temporaryDirectory ??= sys_get_temp_dir();
        $temporaryDirectory = trim($temporaryDirectory);

        if ('' === $temporaryDirectory) {
            throw new RuntimeException('The temporary directory cannot be empty.');
        }

        if (!Path::isAbsolute($temporaryDirectory)) {
            throw new InvalidArgumentException(sprintf('The temporary directory "%s" is not an absolute path.', $temporaryDirectory));
        }

        if (!is_writable($temporaryDirectory)) {
            throw new InvalidArgumentException(sprintf('The temporary directory "%s" is not writable.', $temporaryDirectory));
        }

        $this->baseDirectory = Path::canonicalize(Path::join($temporaryDirectory, self::LIBRARY_DIRECTORY));

        try {
            $this->filesystem->mkdir($this->baseDirectory, 0700);

            if (is_link($this->baseDirectory) || !is_dir($this->baseDirectory)) {
                throw new RuntimeException(sprintf('The temporary base path "%s" must be a real directory, not a symbolic link.', $this->baseDirectory));
            }

            $this->filesystem->chmod($this->baseDirectory, 0700);
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Creating the temporary base directory "%s" failed.', $this->baseDirectory), previous: $e);
        }
    }

    public function createFromStream(
        mixed $stream,
        string|Type|MediaType|null $type = null,
        ?string $name = null,
        ?string $declaredType = null,
    ): TemporaryFileInterface {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('The source must be an open stream resource.');
        }

        $workspace = $this->createWorkspace();

        try {
            $originalName = null !== $name && '' !== trim($name) ? trim($name) : null;
            $safeName = FilenameHelper::sanitize($name) ?? FilenameHelper::generate(12);
            $path = Path::join($workspace, $safeName);

            if (!$output = @fopen($path, 'xb')) {
                throw new RuntimeException(sprintf('Opening the temporary file "%s" for writing failed.', $path));
            }

            try {
                $bytes = stream_copy_to_stream($stream, $output, $this->maximumBytes + 1);

                if (false === $bytes || !fflush($output)) {
                    throw new RuntimeException(sprintf('Writing the temporary file "%s" failed.', $path));
                }
            } finally {
                fclose($output);
            }

            if ($bytes > $this->maximumBytes) {
                throw new FileTooLargeException(sprintf('The temporary file exceeds the maximum size of %d bytes.', $this->maximumBytes));
            }

            if (!@chmod($path, 0600)) {
                throw new RuntimeException(sprintf('Restricting permissions on the temporary file "%s" failed.', $path));
            }

            $mediaType = $this->mediaTypeResolver->resolve($path, $type, $declaredType);
            $finalPath = FilenameHelper::changeExtension($path, $mediaType->getExtension());

            if ($finalPath !== $path) {
                try {
                    $this->filesystem->rename($path, $finalPath, false);
                } catch (FilesystemExceptionInterface $e) {
                    throw new RuntimeException(sprintf('Renaming the temporary file "%s" to "%s" failed.', $path, $finalPath), previous: $e);
                }
            }

            if (false === $size = @filesize($finalPath)) {
                throw new RuntimeException(sprintf('Reading the size of the temporary file "%s" failed.', $finalPath));
            }

            $finalName = basename($finalPath);

            if ('' === $finalName) {
                throw new RuntimeException(sprintf('Generating the temporary filename from "%s" failed.', $finalPath));
            }

            return new TemporaryFile(
                $finalPath,
                $finalName,
                $size,
                $mediaType,
                $originalName,
                $workspace,
            );
        } catch (\Throwable $e) {
            $this->rollback($workspace);

            throw $e;
        }
    }

    public function createFromString(
        string $contents,
        string|Type|MediaType|null $type = null,
        ?string $name = null,
    ): TemporaryFileInterface {
        if (strlen($contents) > $this->maximumBytes) {
            throw new FileTooLargeException(sprintf('The temporary file exceeds the maximum size of %d bytes.', $this->maximumBytes));
        }

        if (!$stream = @fopen('php://temp', 'w+b')) {
            throw new RuntimeException('Opening a temporary memory stream failed.');
        }

        try {
            $length = strlen($contents);
            $offset = 0;

            while ($offset < $length) {
                $written = fwrite($stream, substr($contents, $offset));

                if (false === $written || 0 === $written) {
                    throw new RuntimeException('Writing to the temporary memory stream failed.');
                }

                $offset += $written;
            }

            if (!rewind($stream)) {
                throw new RuntimeException('Writing to the temporary memory stream failed.');
            }

            return $this->createFromStream($stream, $type, $name);
        } finally {
            fclose($stream);
        }
    }

    public function getBaseDirectory(): string
    {
        return $this->baseDirectory;
    }

    public function getMaximumBytes(): int
    {
        return $this->maximumBytes;
    }

    /**
     * @return non-empty-string
     */
    private function createWorkspace(): string
    {
        for ($attempt = 0; $attempt < 10; ++$attempt) {
            $workspace = Path::join($this->baseDirectory, FilenameHelper::generate(20));

            if (@mkdir($workspace, 0700)) {
                return $workspace;
            }
        }

        throw new RuntimeException('Creating a unique temporary workspace failed.');
    }

    private function rollback(string $workspace): void
    {
        if ($this->baseDirectory !== dirname(Path::canonicalize($workspace))) {
            return;
        }

        try {
            if ($this->filesystem->exists($workspace)) {
                $this->filesystem->remove($workspace);
            }
        } catch (FilesystemExceptionInterface) {
        }
    }
}
