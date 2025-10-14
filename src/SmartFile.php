<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;

use function base64_encode;
use function basename;
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
use function rtrim;
use function sprintf;
use function strtolower;
use function substr;
use function trim;
use function unlink;

use const PATHINFO_EXTENSION;

final readonly class SmartFile implements \Stringable
{
    public string $hash;
    public string $path;
    public string $name;
    public string $basename;
    public ?string $extension;
    public string $mimeType;
    public int $size;
    public string $key;

    public function __construct(
        string $hash,
        string $path,
        ?string $name,
        string $mimeType,
        ?int $size = null,
        bool $checkPath = true,
        public bool $delete = true,
    ) {
        if (empty($hash = trim($hash))) {
            throw new InvalidArgumentException('The hash cannot be empty.');
        }

        $this->hash = $hash;

        if (empty($path = trim($path))) {
            throw new InvalidArgumentException('The path cannot be empty.');
        }

        $this->path = $path;

        // Resolve the basename
        $this->basename = basename($this->path);

        // Resolve the name
        $this->name = trim($name ?? '') ?: $this->basename;

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

        // Resolve the extension if present
        $this->extension = pathinfo($this->path, PATHINFO_EXTENSION) ?: null;

        // Resolve the file size
        if ($checkPath && null === $size) {
            $size = @filesize($this->path);
        }

        $this->size = max(0, $size ?: 0);

        if (empty($mimeType = trim($mimeType))) {
            throw new InvalidArgumentException('The type cannot be empty.');
        }

        $this->mimeType = strtolower($mimeType);

        // Generate the remote key
        $key = rtrim($this->hash.'.'.$this->extension, '.');

        if ($prefix = substr($this->hash, 2, 2)) {
            $key = implode('/', [$prefix, $key]);
        }

        if ($prefix = substr($this->hash, 0, 2)) {
            $key = implode('/', [$prefix, $key]);
        }

        $this->key = $key;
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

    public static function createMock(string $path, string $type): self
    {
        // Generate random size [1KB, 4MB]
        $size = random_int(1_024, 4_194_304);

        // Generate random hash based on size
        $hash = hash('sha256', random_bytes($size));

        return new self($hash, $path, null, $type, $size, false, false);
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
