<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Contract\Enum\FileType;
use OneToMany\DataUri\Contract\Record\SmartFileInterface;
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
use function rtrim;
use function sprintf;
use function strtolower;
use function substr;
use function trim;
use function unlink;

use const PATHINFO_EXTENSION;

class SmartFile implements \Stringable, SmartFileInterface
{
    /**
     * @var non-empty-string
     */
    protected string $hash;

    /**
     * @var non-empty-string
     */
    protected string $path;

    /**
     * @var non-empty-string
     */
    protected string $name;
    public ?string $extension;
    public FileType $type;

    /**
     * @var non-empty-string
     */
    protected string $mimeType;

    /**
     * @var int<0, max>
     */
    public int $size;

    /**
     * @var non-empty-string
     */
    public string $remoteKey;

    public function __construct(
        string $hash,
        string $path,
        ?string $name,
        string $mimeType,
        ?int $size = null,
        bool $checkPath = true,
        public bool $delete = true,
    ) {
        // Validate non-empty hash
        if (empty($hash = trim($hash))) {
            throw new InvalidArgumentException('The hash cannot be empty.');
        }

        $this->hash = $hash;

        // Validate non-empty path
        if (empty($path = trim($path))) {
            throw new InvalidArgumentException('The path cannot be empty.');
        }

        $this->path = $path;

        // Resolve and validate non-empty name
        $name = trim($name ?? '') ?: $this->getBasename();

        if (empty($name)) {
            throw new InvalidArgumentException('The name cannot be empty.');
        }

        $this->name = $name;

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

        // Resolve the extension if possible
        $this->extension = pathinfo($this->path, PATHINFO_EXTENSION) ?: null;

        // Resolve the file type
        $this->type = FileType::fromExtension($this->extension);

        // Resolve the file size
        if ($checkPath && null === $size) {
            $size = @filesize($this->path);
        }

        $this->size = max(0, $size ?: 0);

        if (empty($mimeType = trim($mimeType))) {
            throw new InvalidArgumentException('The MIME type cannot be empty.');
        }

        $this->mimeType = strtolower($mimeType);

        // Generate the remote key
        $remoteKey = rtrim($this->hash.'.'.$this->extension, '.');

        if ($prefix = substr($this->hash, 2, 2)) {
            $remoteKey = implode('/', [$prefix, $remoteKey]);
        }

        if ($prefix = substr($this->hash, 0, 2)) {
            $remoteKey = implode('/', [$prefix, $remoteKey]);
        }

        if (strlen($remoteKey) < 8) {
            throw new RuntimeException(sprintf('The remote key "%s" is invalid because it is too short. To fix this, ensure the hash "%s" is four or more characters.', $remoteKey, $hash));
        }

        $this->remoteKey = $remoteKey;
    }

    public function __destruct()
    {
        if ($this->delete && $this->exists()) {
            @unlink($this->path);
        }
    }

    public function __toString(): string
    {
        return $this->path;
    }

    /*
    public string $base64 {
        get => $this->toBase64();
    }

    public string $dataUri {
        get => $this->toDataUri();
    }

    public string $contents {
        get => $this->read();
    }
    */

    public static function createMock(string $path, string $type): self
    {
        // Generate random size [1KB, 4MB]
        $size = random_int(1_024, 4_194_304);

        // Generate random hash based on size
        $hash = hash('sha256', random_bytes($size));

        return new self($hash, $path, null, $type, $size, false, false);
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
    public function getDirectory(): string
    {
        return dirname(realpath($this->path) ?: '/');
    }

    /**
     * @see OneToMany\DataUri\Contract\Record\SmartFileInterface
     */
    public function getBasename(): string
    {
        return basename($this->path);
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
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function equals(self $data, bool $strict = false): bool
    {
        if ($this->hash === $data->hash) {
            if (false === $strict) {
                return true;
            }

            return $this->path === $data->path;
        }

        return false;
    }

    public function exists(): bool
    {
        return file_exists($this->path);
    }

    public function read(): string
    {
        if (false === $contents = @file_get_contents($this->path)) {
            throw new RuntimeException(sprintf('Failed to read the file "%s".', $this->path));
        }

        return $contents;
    }

    public function toBase64(): string
    {
        try {
            return base64_encode($this->read());
        } catch (RuntimeException $e) {
            throw new RuntimeException(sprintf('Failed to encode the file "%s".', $this->path), previous: $e);
        }
    }

    public function toDataUri(): string
    {
        try {
            return sprintf('data:%s;base64,%s', $this->mimeType, $this->toBase64());
        } catch (RuntimeException $e) {
            throw new RuntimeException(sprintf('Failed to generate a data URI representation of the file "%s".', $this->path), previous: $e);
        }
    }
}
