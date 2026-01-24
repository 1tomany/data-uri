<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Contract\Enum\FileType;
use OneToMany\DataUri\Contract\Record\SmartFileInterface;
use OneToMany\DataUri\Exception\AssertValidMimeType;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;

use function base64_encode;
use function basename;
use function dirname;
use function file_exists;
use function file_get_contents;
use function filesize;
use function hash;
use function implode;
use function is_file;
use function is_readable;
use function max;
use function pathinfo;
use function random_bytes;
use function random_int;
use function realpath;
use function sprintf;
use function strlen;
use function strtolower;
use function trim;
use function unlink;

use const PATHINFO_EXTENSION;

readonly class SmartFile implements \Stringable, SmartFileInterface
{
    public string $hash;
    public string $path;
    public string $name;
    public string $basename;
    public ?string $extension;
    public FileType $fileType;
    public string $mimeType;
    public int $size;
    public string $remoteKey;
    public bool $autoDelete;

    private const string SUFFIX_ALPHABET = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    public function __construct(
        string $hash,
        string $path,
        ?string $name,
        string $mimeType,
        ?int $size = null,
        bool $checkPath = true,
        bool $autoDelete = true,
    ) {
        // Validate non-empty hash
        if (empty($hash = trim($hash))) {
            throw new InvalidArgumentException('The hash cannot be empty.');
        }

        // Validate minimum hash length
        if (strlen($hash) < self::MINIMUM_HASH_LENGTH) {
            throw new RuntimeException(sprintf('The hash "%s" must be %d or more characters.', $hash, self::MINIMUM_HASH_LENGTH));
        }

        $this->hash = $hash;

        // Validate non-empty path
        if (empty($path = trim($path))) {
            throw new InvalidArgumentException('The path cannot be empty.');
        }

        $this->path = $path;

        // Resolve the actual file name
        $this->basename = basename($this->path);

        // Resolve the display name
        $displayName = trim($name ?? '') ?: $this->basename;

        if (empty($displayName)) {
            throw new InvalidArgumentException('The name cannot be empty.');
        }

        $this->name = $displayName;

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
        $this->extension = pathinfo(strtolower($this->path), PATHINFO_EXTENSION) ?: null;

        // Determine the FileType based on the extension
        $this->fileType = FileType::fromExtension($this->extension);

        // Force the MIME type for .jsonl files
        if ($this->fileType->isJsonLines()) {
            $mimeType = 'application/jsonl';
        }

        // Validate the MIME type
        $this->mimeType = AssertValidMimeType::assert($mimeType);

        // Calculate the file size
        if ($checkPath && null === $size) {
            $size = @filesize($this->path);
        }

        $this->size = max(0, $size ?: 0);

        // Generate a bucket from the first four characters of the hash
        $remoteKey = implode('/', [$hash[0].$hash[1], $hash[2].$hash[3]]);

        try {
            $suffix = new \Random\Randomizer()->getBytesFromString(self::SUFFIX_ALPHABET, 12);

            // Append the extension to the suffix
            if (false === empty($ext = $this->extension)) {
                $suffix = implode('.', [$suffix, $ext]);
            }

            $this->remoteKey = $remoteKey.'.'.$suffix;
        } catch (\Random\RandomException|\Random\RandomError $e) {
            throw new RuntimeException('Failed to generate a sufficiently random suffix.', previous: $e);
        }

        $this->autoDelete = $autoDelete;
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

    public static function createMock(string $path, string $mimeType): self
    {
        // Generate random size [1KB, 4MB]
        $size = random_int(1_024, 4_194_304);

        // Generate random hash based on size
        $hash = hash('sha256', random_bytes($size));

        return new self($hash, $path, null, $mimeType, $size, false, false);
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
        return dirname(realpath($this->path) ?: '/');
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\SmartFileInterface
     */
    public function getBasename(): string
    {
        return $this->basename;
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
    public function getFileType(): FileType
    {
        return $this->fileType;
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\SmartFileInterface
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
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
            return sprintf('data:%s;base64,%s', $this->mimeType, $this->toBase64());
        } catch (RuntimeException $e) {
            throw new RuntimeException(sprintf('Failed to generate a data URI representation of the file "%s".', $this->path), previous: $e);
        }
    }
}
