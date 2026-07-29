<?php

namespace OneToMany\DataUri\Record;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\Exception\ExceptionInterface as DataUriExceptionInterface;
use OneToMany\DataUri\Contract\Record\TemporaryFileInterface;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use OneToMany\DataUri\Helper\FilenameHelper;
use Symfony\Component\Filesystem\Path;

use function assert;
use function file_exists;
use function file_get_contents;
use function hash_file;
use function implode;
use function is_dir;
use function is_link;
use function is_string;
use function OneToMany\IsEmpty\is_empty;
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
    private ?string $root = null;

    /**
     * @var non-empty-string
     */
    private readonly string $name;

    /**
     * @var non-negative-int
     */
    private readonly int $size;

    /**
     * @var non-empty-lowercase-string
     */
    private readonly string $hash;

    /**
     * @var non-empty-string
     */
    private readonly string $key;

    /**
     * @param non-empty-string $name
     */
    public function __construct(
        string $path,
        ?string $root,
        string $name,
        int $size,
        private readonly Type $type,
    ) {
        if (is_empty($path = trim($path))) {
            throw new InvalidArgumentException('The path cannot be empty.');
        }

        $this->path = $path;

        $this->root = $this->validateRoot(
            path: $this->path, root: $root,
        );

        if (is_empty($name = trim($name))) {
            throw new InvalidArgumentException('The name cannot be empty.');
        }

        $this->name = $name;

        if ($size < 0) {
            throw new InvalidArgumentException('The size cannot be negative.');
        }

        $this->size = $size;

        $this->hash = $this->generateHash(...[
            'path' => $this->path,
        ]);

        $this->key = $this->generateKey(
            $this->hash, $this->root, $this->name,
        );
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
    public function getRoot(): ?string
    {
        return $this->root;
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
    public function equals(TemporaryFileInterface $file, bool $strict = false): bool
    {
        return $this->getHash() === $file->getHash() ? (!$strict ?: $this->getPath() === $file->getPath()) : false;
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
        $this->root = null;

        return $this->getPath();
    }

    public function isManaged(): bool
    {
        return null !== $this->root;
    }

    /**
     * @return non-empty-lowercase-string
     */
    private function generateHash(string $path): string
    {
        if (!$hash = @hash_file('sha256', $path)) {
            throw new RuntimeException(sprintf('Generating the hash of the file "%s" failed.', $path));
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

            if (!is_empty($root)) {
                $keyBits[] = $root;
            }
        }

        return implode('/', [...$keyBits, ...[$name]]);
    }

    /**
     * @param non-empty-string $path
     *
     * @throws InvalidArgumentException
     *
     * @return ?non-empty-string
     */
    private function validateRoot(string $path, ?string $root): ?string
    {
        if (null === $root) {
            return null;
        }

        $root = trim($root);

        if (is_empty($root, false) || !Path::isAbsolute($root)) {
            throw new InvalidArgumentException('The root directory must be a non-empty absolute path.');
        }

        $root = Path::canonicalize($root);

        if ($root !== Path::getDirectory(Path::canonicalize($path))) {
            throw new InvalidArgumentException(sprintf('The root "%s" must be a direct child of the path "%s".', $root, $path));
        }

        return is_empty($root) ? null : $root;
    }

    private function cleanup(bool $throw): void
    {
        if (!$this->isManaged()) {
            return;
        }

        $failure = null;

        if (file_exists($this->getPath()) || is_link($this->getPath())) {
            if (is_dir($this->getPath()) && !is_link($this->getPath())) {
                $failure = new RuntimeException(sprintf('Refusing to recursively delete the directory at temporary file path "%s".', $this->getPath()));
            } elseif (!@unlink($this->getPath())) {
                $failure = new RuntimeException(sprintf('Deleting the temporary file "%s" failed.', $this->getPath()));
            }
        }

        if (null === $failure && null !== $this->getRoot() && is_dir($this->getRoot()) && !@rmdir($this->getRoot())) {
            $failure = new RuntimeException(sprintf('Removing the temporary directory "%s" failed because it is not empty or is not removable.', $this->getRoot()));
        }

        if (null === $failure) {
            $this->detach();
        } else {
            if ($throw) {
                throw $failure;
            }
        }
    }
}
