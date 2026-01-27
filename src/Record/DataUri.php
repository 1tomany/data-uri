<?php

namespace OneToMany\DataUri\Record;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\Exception\ExceptionInterface as DataUriExceptionInterface;
use OneToMany\DataUri\Contract\Record\DataUriInterface;
use OneToMany\DataUri\Exception\RuntimeException;

use function file_exists;
use function file_get_contents;
use function sprintf;
use function unlink;

class DataUri implements DataUriInterface
{
    /**
     * @param non-empty-lowercase-string $hash
     * @param non-empty-string $path
     * @param non-empty-string $name
     * @param non-negative-int $size
     * @param non-empty-string $uri
     */
    public function __construct(
        public readonly string $hash,
        public readonly string $path,
        public readonly string $name,
        public readonly int $size,
        public readonly Type $type,
        public readonly string $uri,
    ) {
    }

    public function __destruct()
    {
        if (file_exists($this->path)) {
            @unlink($this->path);
        }
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\DataUriInterface
     */
    public function __toString(): string
    {
        return $this->path;
    }

    /**
     * @var ?non-empty-lowercase-string
     */
    public ?string $extension {
        get => $this->getExtension();
    }

    /**
     * @var non-empty-lowercase-string
     */
    public string $format {
        get => $this->getFormat();
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\DataUriInterface
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\DataUriInterface
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\DataUriInterface
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\DataUriInterface
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\DataUriInterface
     */
    public function getType(): Type
    {
        return $this->type;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\DataUriInterface
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\DataUriInterface
     */
    public function getExtension(): ?string
    {
        return $this->type->getExtension();
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\DataUriInterface
     */
    public function getFormat(): string
    {
        return $this->type->getFormat();
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\DataUriInterface
     */
    public function equals(DataUriInterface $file, bool $strict = false): bool
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
     * @see OneToMany\DataUri\Contract\Record\DataUriInterface
     */
    public function exists(): bool
    {
        return file_exists($this->path);
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\DataUriInterface
     */
    public function read(): string
    {
        if (false === $contents = @file_get_contents($this->path)) {
            throw new RuntimeException(sprintf('Reading the file "%s" failed.', $this->path));
        }

        return $contents;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\DataUriInterface
     */
    public function toBase64(): string
    {
        try {
            return base64_encode($this->read());
        } catch (DataUriExceptionInterface $e) {
            throw new RuntimeException(sprintf('Encoding the file "%s" failed.', $this->path), previous: $e);
        }
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\DataUriInterface
     */
    public function toUri(): string
    {
        try {
            return sprintf('data:%s;base64,%s', $this->format, $this->toBase64());
        } catch (DataUriExceptionInterface $e) {
            throw new RuntimeException(sprintf('Generating the URI of the file "%s" failed.', $this->path), previous: $e);
        }
    }
}
