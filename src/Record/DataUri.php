<?php

namespace OneToMany\DataUri\Record;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\Exception\ExceptionInterface as DataUriExceptionInterface;
use OneToMany\DataUri\Contract\Record\DataUriInterface;
use OneToMany\DataUri\Exception\RuntimeException;
use OneToMany\DataUri\Helper\FilenameHelper;

use function file_exists;
use function file_get_contents;
use function hash_file;
use function implode;
use function sprintf;
use function strlen;
use function substr;
use function unlink;

class DataUri implements DataUriInterface
{
    /**
     * @param non-empty-string $path
     * @param non-empty-string $name
     * @param non-negative-int $size
     */
    public function __construct(
        public readonly string $path,
        public readonly string $name,
        public readonly int $size,
        public readonly Type $type,
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
     * @var non-empty-lowercase-string
     */
    public string $hash {
        get {
            if (!$this->isPropInitialized(__PROPERTY__)) {
                $this->hash = $this->generateHash();
            }

            return $this->hash;
        }
    }

    /**
     * @var non-empty-string
     */
    public string $key {
        get {
            if (!$this->isPropInitialized(__PROPERTY__)) {
                $this->key = $this->generateKey();
            }

            return $this->key;
        }
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
    public function getKey(): string
    {
        return $this->key;
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
        return $this->hash === $file->getHash() ? (!$strict ?: $this->path === $file->getPath()) : false;
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
    public function toDataUri(): string
    {
        try {
            return sprintf('data:%s;base64,%s', $this->format, $this->toBase64());
        } catch (DataUriExceptionInterface $e) {
            throw new RuntimeException(sprintf('Encoding the file "%s" as a data URI failed.', $this->path), previous: $e);
        }
    }

    private function isPropInitialized(string $property): bool
    {
        return new \ReflectionProperty($this, $property)->isInitialized($this);
    }

    /**
     * @return non-empty-lowercase-string
     */
    private function generateHash(): string
    {
        if (!$hash = @hash_file('sha256', $this->path)) {
            throw new RuntimeException(sprintf('Generating the hash of the file "%s" failed.', $this->path));
        }

        if (strlen($hash) < DataUriInterface::MINIMUM_HASH_LENGTH) {
            throw new RuntimeException(sprintf('The hash "%s" must be %d or more characters.', $hash, DataUriInterface::MINIMUM_HASH_LENGTH));
        }

        return $hash;
    }

    /**
     * @return non-empty-string
     */
    private function generateKey(): string
    {
        try {
            return FilenameHelper::changeExtension(implode('/', [substr($this->hash, 0, 2), substr($this->hash, 2, 2), FilenameHelper::generate(12)]), $this->extension);
        } catch (DataUriExceptionInterface $e) {
            throw new RuntimeException(sprintf('Generating the key for the file "%s" failed.', $this->path), previous: $e);
        }
    }
}
