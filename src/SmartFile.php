<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Contract\Enum\FileType;
use OneToMany\DataUri\Contract\Record\SmartFileInterface;
use OneToMany\DataUri\Exception\AssertValidMimeType;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use Symfony\Component\Filesystem\Path;

use function base64_encode;
use function basename;
use function file_exists;
use function file_get_contents;
use function hash;
use function implode;
use function is_file;
use function is_readable;
use function pathinfo;
use function sprintf;
use function strlen;
use function strtolower;
use function unlink;

use const PATHINFO_EXTENSION;

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
        public FileType $type,
        public string $format,
        public int $size,
        public string $remoteKey,
        bool $checkPath = true,
        public bool $autoDelete = true,
    ) {
        // Validate hash is not empty
        if (empty($this->hash)) {
            throw new InvalidArgumentException('The hash cannot be empty.');
        }

        // Validate minimum hash length
        if (strlen($this->hash) < self::MINIMUM_HASH_LENGTH) {
            throw new RuntimeException(sprintf('The hash "%s" must be %d or more characters.', $this->hash, self::MINIMUM_HASH_LENGTH));
        }

        // Validate non-empty path
        if (empty($this->path)) {
            throw new InvalidArgumentException('The path cannot be empty.');
        }

        // Resolve the actual file name
        // $basename = basename($this->path);

        // if (empty($basename = basename($this->path))) {
        //     throw new InvalidArgumentException('The basename cannot be empty.');
        // }

        // $this->basename = $basename;

        // File access validation tests
        if ($checkPath && !file_exists($this->path)) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not exist.', $this->path));
        }

        if ($checkPath && !is_readable($this->path)) {
            throw new InvalidArgumentException(sprintf('The file "%s" is not readable.', $this->path));
        }

        if ($checkPath && !is_file($this->path)) {
            throw new InvalidArgumentException(sprintf('The path "%s" is not a file.', $this->path));
        }

        // Resolve the extension if available
        // $this->extension = strtolower(pathinfo($this->path, PATHINFO_EXTENSION)) ?: null;

        // Determine the FileType based on the extension
        // $this->type = FileType::fromExtension($this->extension);

        // // Force the MIME type for .jsonl files
        // if ($this->type->isJsonLines()) {
        //     $format = 'application/jsonl';
        // }

        // Validate the MIME type
        // $this->format = AssertValidMimeType::assert($format);

        // Generate a bucket from the first four characters of the hash
        // $remoteKey = implode('/', [$hash[0].$hash[1], $hash[2].$hash[3]]);

        // try {
        //     $suffix = new \Random\Randomizer()->getBytesFromString(self::SUFFIX_ALPHABET, 10);

        //     // Append the extension to the suffix
        //     if (false === empty($ext = $this->extension)) {
        //         $suffix = implode('.', [$suffix, $ext]);
        //     }

        //     // Append the suffix to the remote key
        //     $this->remoteKey = implode('/', [$remoteKey, $suffix]);
        // } catch (\Random\RandomException|\Random\RandomError $e) {
        //     throw new RuntimeException('Failed to generate a sufficiently random remote key.', previous: $e);
        // }
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
    public function getType(): FileType
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
