<?php

namespace OneToMany\DataUri\Record;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\Exception\ExceptionInterface as DataUriExceptionInterface;
use OneToMany\DataUri\Contract\Record\TemporaryFileInterface;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use Override;
use Symfony\Component\Filesystem\Path;

use function assert;
use function file_exists;
use function file_get_contents;
use function hash_file;
use function implode;
use function is_dir;
use function is_link;
use function is_string;
use function rmdir;
use function sprintf;
use function strlen;
use function substr;
use function trim;
use function unlink;

class TemporaryFile implements TemporaryFileInterface
{
    /**
     * @var non-empty-string
     */
    private readonly string $path;

    /**
     * @var ?non-empty-string
     */
    private readonly ?string $base;

    /**
     * @var non-empty-string
     */
    private readonly string $name;

    /**
     * @var non-negative-int
     */
    private readonly int $size;

    private readonly Type $type;

    /**
     * @var non-empty-lowercase-string
     */
    private readonly string $hash;

    /**
     * @var non-empty-string
     */
    private readonly string $key;

    private bool $isManaged = false;

    public function __construct(
        string $path,
        ?string $base,
        string $name,
        int $size,
        Type $type,
    ) {
        if ('' === $path = Path::canonicalize(trim($path))) {
            throw new InvalidArgumentException('The path cannot be empty.');
        }

        $this->path = $path;

        if (is_dir($this->path) || is_link($this->path)) {
            throw new InvalidArgumentException(sprintf('The path "%s" cannot be a directory or link.', $this->path));
        }

        $this->base = $this->validateBase(
            $this->getPath(), base: $base,
        );

        if ('' === $name = trim($name)) {
            throw new InvalidArgumentException('The name cannot be empty.');
        }

        $this->name = $name;

        if ($size < 0) {
            throw new InvalidArgumentException('The size cannot be negative.');
        }

        $this->size = $size;
        $this->type = $type;

        $this->hash = $this->generateHash(...[
            'path' => $this->getPath(),
        ]);

        $this->key = $this->generateKey(...[
            'hash' => $this->getHash(),
            'root' => $this->getBase(),
            'name' => $this->getName(),
        ]);

        $this->isManaged = true;
    }

    public function __destruct()
    {
        $this->cleanup(false);
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    public function __toString(): string
    {
        return $this->getPath();
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    public function getBase(): ?string
    {
        return $this->base;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    public function getType(): Type
    {
        return $this->type;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    public function getExtension(): ?string
    {
        return $this->type->getExtension();
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    public function getFormat(): string
    {
        return $this->type->getFormat();
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    #[\Override]
    public function isEqual(TemporaryFileInterface $file): bool
    {
        return $this->getHash() === $file->getHash();
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    #[\Override]
    public function isSame(TemporaryFileInterface $file): bool
    {
        return $this->isEqual($file) && $this->getPath() === $file->getPath();
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    public function exists(): bool
    {
        return file_exists($this->getPath());
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    public function read(): string
    {
        if (false === $contents = @file_get_contents($this->getPath())) {
            throw new RuntimeException(sprintf('Reading the file "%s" failed.', $this->getPath()));
        }

        return $contents;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    public function toBase64(): string
    {
        try {
            return base64_encode($this->read());
        } catch (DataUriExceptionInterface $e) {
            throw new RuntimeException(sprintf('Encoding the file "%s" failed.', $this->getPath()), previous: $e);
        }
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    public function toDataUri(): string
    {
        try {
            return sprintf('data:%s;base64,%s', $this->getFormat(), $this->toBase64());
        } catch (DataUriExceptionInterface $e) {
            throw new RuntimeException(sprintf('Encoding the file "%s" as a data URI failed.', $this->getPath()), previous: $e);
        }
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    public function delete(): void
    {
        $this->cleanup(true);
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    public function detach(): string
    {
        $this->isManaged = false;

        return $this->getPath();
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\TemporaryFileInterface
     */
    public function isManaged(): bool
    {
        return $this->isManaged;
    }

    /**
     * @return non-empty-lowercase-string
     *
     * @throws RuntimeException when generating a hash of the file fails
     * @throws RuntimeException when the length of the hash is too short
     */
    private function generateHash(string $path): string
    {
        if (!$hash = @hash_file('sha256', $path)) {
            throw new RuntimeException(sprintf('Generating a hash of the file "%s" failed.', $path));
        }

        if (strlen($hash) < TemporaryFileInterface::MINIMUM_HASH_LENGTH) {
            throw new RuntimeException(sprintf('The hash "%s" must be %d or more characters.', $hash, TemporaryFileInterface::MINIMUM_HASH_LENGTH));
        }

        return $hash;
    }

    /**
     * @param non-empty-lowercase-string $hash
     * @param ?non-empty-string $root
     * @param non-empty-string $name
     *
     * @return non-empty-string
     */
    private function generateKey(string $hash, ?string $root, string $name): string
    {
        assert(strlen($hash) >= TemporaryFileInterface::MINIMUM_HASH_LENGTH);

        $keyBits = [
            substr($hash, 0, 2),
            substr($hash, 2, 2),
        ];

        if (is_string($root)) {
            $root = basename($root);

            if ('' !== $root) {
                $keyBits[] = $root;
            }
        }

        return implode('/', [...$keyBits, ...[$name]]);
    }

    /**
     * @param non-empty-string $path
     *
     * @return ?non-empty-string
     *
     * @throws InvalidArgumentException when the base directory is empty
     * @throws InvalidArgumentException when the base directory is not an absolute path
     * @throws InvalidArgumentException when the path is not a direct child of the base
     */
    private function validateBase(string $path, ?string $base): ?string
    {
        if (null === $base) {
            return null;
        }

        if ('' !== $base = trim($base)) {
            if (!Path::isAbsolute($base)) {
                throw new InvalidArgumentException(sprintf('The base directory "%s" must be an absolute path.', $base));
            }

            $base = Path::canonicalize($base);

            if ($base !== Path::getDirectory($path)) {
                throw new InvalidArgumentException(sprintf('The path "%s" must be a direct child of the base directory "%s".', $path, $base));
            }
        }

        return '' === $base ? null : $base;
    }

    private function cleanup(bool $throw): void
    {
        if (!$this->isManaged()) {
            return;
        }

        if (file_exists($this->path) && !@unlink($this->path)) {
            $error = sprintf('Deleting the file "%s" failed.', $this->path);
        }

        if (!isset($error) && null !== $this->base) {
            if (file_exists($this->base) && !@rmdir($this->base)) {
                $error = sprintf('Removing the root directory "%s" failed.', $this->base);
            }
        }

        if (isset($error) && $throw) {
            throw new RuntimeException($error);
        }

        $this->detach();
    }
}
