<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\Exception\ExceptionInterface as DataUriExceptionInterface;
use OneToMany\DataUri\Contract\TemporaryFileInterface;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use Symfony\Component\Filesystem\Path;

use function base64_encode;
use function dirname;
use function file_exists;
use function file_get_contents;
use function fopen;
use function hash_file;
use function is_dir;
use function is_link;
use function rmdir;
use function sprintf;
use function strlen;
use function trim;
use function unlink;

/**
 * An owned temporary file with deterministic and best-effort cleanup APIs.
 */
class TemporaryFile implements TemporaryFileInterface
{
    private bool $managed;

    private ?string $ownedDirectory;

    public readonly MediaType $mediaType;

    public readonly Type $type;

    /**
     * The owned directory must be the exact, unique parent created for this file.
     */
    public function __construct(
        public readonly string $path,
        public readonly string $name,
        public readonly int $size,
        string|Type|MediaType $mediaType,
        public readonly ?string $originalName = null,
        ?string $ownedDirectory = null,
    ) {
        if ('' === trim($this->path)) {
            throw new InvalidArgumentException('The temporary file path cannot be empty.');
        }

        if ('' === trim($this->name)) {
            throw new InvalidArgumentException('The temporary file name cannot be empty.');
        }

        if ($this->size < 0) {
            throw new InvalidArgumentException('The temporary file size cannot be negative.');
        }

        $this->mediaType = MediaType::create($mediaType);
        $this->type = $this->mediaType->type;
        $this->ownedDirectory = $this->validateOwnedDirectory($ownedDirectory);
        $this->managed = null !== $this->ownedDirectory;
    }

    public function __destruct()
    {
        $this->cleanup(false);
    }

    public function __toString(): string
    {
        return $this->path;
    }

    public function getHash(): string
    {
        if (!$hash = @hash_file('sha256', $this->path)) {
            throw new RuntimeException(sprintf('Generating the hash of the file "%s" failed.', $this->path));
        }

        if (strlen($hash) < self::MINIMUM_HASH_LENGTH) {
            throw new RuntimeException(sprintf('The hash "%s" must be %d or more characters.', $hash, self::MINIMUM_HASH_LENGTH));
        }

        return $hash;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getMediaType(): MediaType
    {
        return $this->mediaType;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getExtension(): ?string
    {
        return $this->mediaType->getExtension();
    }

    public function getFormat(): string
    {
        return $this->mediaType->value;
    }

    public function hasSameContentAs(TemporaryFileInterface $file): bool
    {
        return $this->getHash() === $file->getHash();
    }

    public function refersToSameFileAs(TemporaryFileInterface $file): bool
    {
        return $this->hasSameContentAs($file) && $this->path === $file->getPath();
    }

    public function equals(TemporaryFileInterface $file, bool $strict = false): bool
    {
        return $strict ? $this->refersToSameFileAs($file) : $this->hasSameContentAs($file);
    }

    public function exists(): bool
    {
        return file_exists($this->path);
    }

    public function open()
    {
        if (!$stream = @fopen($this->path, 'rb')) {
            throw new RuntimeException(sprintf('Opening the file "%s" failed.', $this->name));
        }

        return $stream;
    }

    public function read(): string
    {
        if (false === $contents = @file_get_contents($this->path)) {
            throw new RuntimeException(sprintf('Reading the file "%s" failed.', $this->name));
        }

        return $contents;
    }

    public function toBase64(): string
    {
        try {
            return base64_encode($this->read());
        } catch (DataUriExceptionInterface $e) {
            throw new RuntimeException(sprintf('Encoding the file "%s" as a base64 string failed.', $this->name), previous: $e);
        }
    }

    public function toDataUri(): string
    {
        try {
            return sprintf('data:%s;base64,%s', $this->getFormat(), $this->toBase64());
        } catch (DataUriExceptionInterface $e) {
            throw new RuntimeException(sprintf('Encoding the file "%s" as a data URI failed.', $this->name), previous: $e);
        }
    }

    public function delete(): void
    {
        $this->cleanup(true);
    }

    public function detach(): string
    {
        $this->managed = false;
        $this->ownedDirectory = null;

        return $this->path;
    }

    public function isManaged(): bool
    {
        return $this->managed;
    }

    private function validateOwnedDirectory(?string $ownedDirectory): ?string
    {
        if (null === $ownedDirectory) {
            return null;
        }

        $ownedDirectory = trim($ownedDirectory);

        if ('' === $ownedDirectory || !Path::isAbsolute($ownedDirectory)) {
            throw new InvalidArgumentException('The owned temporary directory must be a non-empty absolute path.');
        }

        $canonicalDirectory = Path::canonicalize($ownedDirectory);
        $canonicalPath = Path::canonicalize($this->path);

        if ($canonicalDirectory !== dirname($canonicalPath)) {
            throw new InvalidArgumentException(sprintf('The file "%s" must be a direct child of its owned directory.', $this->path));
        }

        return $canonicalDirectory;
    }

    private function cleanup(bool $throw): void
    {
        if (!$this->managed) {
            return;
        }

        $failure = null;

        if (file_exists($this->path) || is_link($this->path)) {
            if (is_dir($this->path) && !is_link($this->path)) {
                $failure = new RuntimeException(sprintf('Refusing to recursively delete the directory at temporary file path "%s".', $this->path));
            } elseif (!@unlink($this->path)) {
                $failure = new RuntimeException(sprintf('Deleting the temporary file "%s" failed.', $this->path));
            }
        }

        if (null === $failure) {
            if (null !== $this->ownedDirectory && is_dir($this->ownedDirectory) && !@rmdir($this->ownedDirectory)) {
                $failure = new RuntimeException(sprintf('Removing the temporary directory "%s" failed because it is not empty or is not removable.', $this->ownedDirectory));
            }
        }

        if (null === $failure) {
            $this->managed = false;
            $this->ownedDirectory = null;

            return;
        }

        if ($throw) {
            throw $failure;
        }
    }
}
