<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\Record\SmartFileInterface;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use Symfony\Component\Filesystem\Path;

use function base64_encode;
use function file_exists;
use function file_get_contents;
use function hash;
use function is_file;
use function is_readable;
use function sprintf;
use function strlen;
use function unlink;

readonly class SmartFile implements \Stringable, SmartFileInterface
{
    /**
     * @param non-empty-lowercase-string $hash
     * @param non-empty-string $path
     * @param non-empty-string $name
     * @param ?non-empty-lowercase-string $extension
     * @param non-empty-lowercase-string $format
     * @param non-negative-int $size
     * @param non-empty-string $remoteKey
     */
    public function __construct(
        public string $hash,
        public string $path,
        public string $name,
        public ?string $extension,
        public Type $type,
        public string $format,
        public int $size,
        public string $remoteKey,
        public bool $autoDelete = true,
    ) {
        // Validate non-empty hash
        if (empty($this->hash)) {
            throw new InvalidArgumentException('The hash cannot be empty.');
        }

        // Validate minimum hash length
        if (strlen($this->hash) < self::MINIMUM_HASH_LENGTH) {
            throw new InvalidArgumentException(sprintf('The hash "%s" must be %d or more characters.', $this->hash, self::MINIMUM_HASH_LENGTH));
        }

        // Validate non-empty path
        if (empty($this->path)) {
            throw new InvalidArgumentException('The path cannot be empty.');
        }

        // File access tests
        if (!file_exists($this->path)) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not exist.', $this->path));
        }

        if (!is_readable($this->path)) {
            throw new InvalidArgumentException(sprintf('The file "%s" is not readable.', $this->path));
        }

        if (!is_file($this->path)) {
            throw new InvalidArgumentException(sprintf('The path "%s" is not a file.', $this->path));
        }
    }

    public function __destruct()
    {
        if ($this->autoDelete) {
            if ($this->exists()) {
                @unlink($this->path);
            }
        }
    }

    public function __toString(): string
    {
        return $this->path;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\SmartFileInterface
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\SmartFileInterface
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\SmartFileInterface
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\SmartFileInterface
     */
    public function getDirectory(): string
    {
        return Path::getDirectory($this->path);
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\SmartFileInterface
     */
    public function getExtension(): ?string
    {
        return $this->extension;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\SmartFileInterface
     */
    public function getType(): Type
    {
        return $this->type;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\SmartFileInterface
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\SmartFileInterface
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\SmartFileInterface
     */
    public function getRemoteKey(): string
    {
        return $this->remoteKey;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\SmartFileInterface
     */
    public function equals(SmartFileInterface $file, bool $strict = false): bool
    {
        if ($this->hash === $file->getHash()) {
            if (false === $strict) {
                return true;
            }

            return $this->path === $file->getPath();
        }

        return false;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\SmartFileInterface
     */
    public function exists(): bool
    {
        return file_exists($this->path);
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\SmartFileInterface
     */
    public function read(): string
    {
        if (false === $contents = @file_get_contents($this->path)) {
            throw new RuntimeException(sprintf('Failed to read the file "%s".', $this->path));
        }

        return $contents;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\SmartFileInterface
     */
    public function toBase64(): string
    {
        try {
            return base64_encode($this->read());
        } catch (RuntimeException $e) {
            throw new RuntimeException(sprintf('Failed to encode the file "%s".', $this->path), previous: $e);
        }
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\SmartFileInterface
     */
    public function toDataUri(): string
    {
        try {
            return sprintf('data:%s;base64,%s', $this->format, $this->toBase64());
        } catch (RuntimeException $e) {
            throw new RuntimeException(sprintf('Failed to generate a data URI representation of the file "%s".', $this->path), previous: $e);
        }
    }
}
