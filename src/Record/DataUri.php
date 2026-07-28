<?php

namespace OneToMany\DataUri\Record;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\Exception\ExceptionInterface as DataUriExceptionInterface;
use OneToMany\DataUri\Contract\Record\DataUriInterface;
use OneToMany\DataUri\Exception\RuntimeException;
use OneToMany\DataUri\Helper\FilenameHelper;
use Symfony\Component\Filesystem\Exception\ExceptionInterface as FilesystemExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function basename;
use function dirname;
use function file_exists;
use function file_get_contents;
use function hash_file;
use function implode;
use function rmdir;
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
     * @param ?non-empty-string $source
     */
    public function __construct(
        public readonly string $path,
        public readonly string $name,
        public readonly int $size,
        public readonly Type $type,
        public readonly ?string $source = null,
    ) {
    }

    public function __destruct()
    {
        $this->cleanup();
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
    public function getSource(): ?string
    {
        return $this->source;
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
     *
     * @throws RuntimeException when reading the file fails
     */
    public function read(): string
    {
        if (false === $contents = @file_get_contents($this->path)) {
            throw new RuntimeException(sprintf('Reading the file "%s" failed.', $this->name));
        }

        return $contents;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\DataUriInterface
     *
     * @throws RuntimeException when encoding the file as a base64 string fails
     */
    public function toBase64(): string
    {
        try {
            return base64_encode($this->read());
        } catch (DataUriExceptionInterface $e) {
            throw new RuntimeException(sprintf('Encoding the file "%s" as a base64 string failed.', $this->name), previous: $e);
        }
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\DataUriInterface
     *
     * @throws RuntimeException when encoding the file as a data URI fails
     */
    public function toDataUri(): string
    {
        try {
            return sprintf('data:%s;base64,%s', $this->format, $this->toBase64());
        } catch (DataUriExceptionInterface $e) {
            throw new RuntimeException(sprintf('Encoding the file "%s" as a data URI failed.', $this->name), previous: $e);
        }
    }

    private function isPropInitialized(string $property): bool
    {
        return new \ReflectionProperty($this, $property)->isInitialized($this);
    }

    /**
     * @return non-empty-lowercase-string
     *
     * @throws RuntimeException when generating the hash fails
     * @throws RuntimeException when the hash is too short
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
     *
     * @throws RuntimeException when generating a key fails
     */
    private function generateKey(): string
    {
        try {
            $key = $this->name;

            if (null !== $this->source) {
                $prefix = FilenameHelper::generate(6);

                if (!empty($dir = dirname($this->path))) {
                    $prefix = basename($dir) ?: $prefix;
                }

                $key = implode('/', [$prefix, $this->name]);
            }

            return implode('/', [substr($this->hash, 0, 2), substr($this->hash, 2, 2), $key]);
        } catch (DataUriExceptionInterface $e) {
            throw new RuntimeException(sprintf('Generating the key for the file "%s" failed.', $this->name), previous: $e);
        }
    }

    private function cleanup(): void
    {
        $fs = new Filesystem();

        try {
            $parent = dirname($this->path);

            if ($parent && $fs->exists($parent)) {
                // $fs->remove([$this->path, $parent]);
            }
        } catch (FilesystemExceptionInterface) {
        }
    }
}
