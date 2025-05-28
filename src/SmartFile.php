<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Exception\ConstructionFailedByteCountNotCalculatedException;
use OneToMany\DataUri\Exception\ConstructionFailedByteCountNotProvidedException;
use OneToMany\DataUri\Exception\ConstructionFailedContentTypeNotProvidedException;
use OneToMany\DataUri\Exception\ConstructionFailedFileContentsNotReadableException;
use OneToMany\DataUri\Exception\ConstructionFailedFileDoesNotExistException;
use OneToMany\DataUri\Exception\ConstructionFailedFileNotFileException;
use OneToMany\DataUri\Exception\ConstructionFailedFileNotReadableException;
use OneToMany\DataUri\Exception\ConstructionFailedFilePathNotProvidedException;
use OneToMany\DataUri\Exception\ConstructionFailedHashNotProvidedException;
use OneToMany\DataUri\Exception\EncodingFailedInvalidFilePathException;

use function array_unshift;
use function base64_encode;
use function basename;
use function file_exists;
use function file_get_contents;
use function filesize;
use function hash;
use function implode;
use function is_file;
use function is_readable;
use function pathinfo;
use function random_bytes;
use function random_int;
use function sprintf;
use function strtolower;
use function substr;
use function trim;
use function unlink;
use function vsprintf;

use const PATHINFO_EXTENSION;

final readonly class SmartFile implements \Stringable
{
    public string $hash;
    public string $filePath;
    public string $fileName;
    public string $contentType;
    public int $byteCount;
    public string $displayName;
    public string $extension;
    public string $remoteKey;

    public function __construct(
        string $filePath,
        ?string $hash,
        string $contentType,
        ?int $byteCount = null,
        ?string $displayName = null,
        bool $checkExists = true,
        public bool $selfDestruct = true,
    ) {
        $this->filePath = trim($filePath);

        if (empty($this->filePath)) {
            throw new ConstructionFailedFilePathNotProvidedException();
        }

        $this->fileName = basename($this->filePath);

        if (true === $checkExists) {
            if (!file_exists($this->filePath)) {
                throw new ConstructionFailedFileDoesNotExistException($this->filePath);
            }

            if (!is_readable($this->filePath)) {
                throw new ConstructionFailedFileNotReadableException($this->filePath);
            }

            if (!is_file($this->filePath)) {
                throw new ConstructionFailedFileNotFileException($this->filePath);
            }

            if (null === $hash) {
                if (false === $contents = @file_get_contents($this->filePath)) {
                    throw new ConstructionFailedFileContentsNotReadableException($this->filePath);
                }

                $hash = hash('sha256', $contents);
            }

            if (false === $byteCount = ($byteCount ?? filesize($this->filePath))) {
                throw new ConstructionFailedByteCountNotCalculatedException($this->filePath);
            }
        }

        if (null === $hash) {
            throw new ConstructionFailedHashNotProvidedException($this->filePath);
        }

        $this->hash = $hash;

        if (null === $byteCount) {
            throw new ConstructionFailedByteCountNotProvidedException($this->filePath);
        }

        $this->byteCount = $byteCount;

        if (empty($contentType = trim($contentType))) {
            throw new ConstructionFailedContentTypeNotProvidedException($this->filePath);
        }

        $this->contentType = strtolower($contentType);

        // Resolve the Display Name
        $this->displayName = trim($displayName ?? '') ?: $this->fileName;

        // Resolve the File Extension
        $this->extension = pathinfo($this->filePath, PATHINFO_EXTENSION);

        // Generate the Remote Key
        $remoteKeyPrefix = vsprintf('%s.%s', [
            $this->hash, $this->extension,
        ]);

        $remoteKeyBits = [$remoteKeyPrefix];

        if ($prefix = substr($hash, 2, 2)) {
            array_unshift($remoteKeyBits, $prefix);
        }

        if ($prefix = substr($hash, 0, 2)) {
            array_unshift($remoteKeyBits, $prefix);
        }

        $this->remoteKey = implode('/', $remoteKeyBits);
    }

    public function __destruct()
    {
        if ($this->selfDestruct) {
            if (file_exists($this->filePath)) {
                @unlink($this->filePath);
            }
        }
    }

    public function __toString(): string
    {
        return $this->filePath;
    }

    public static function createMock(string $filePath, string $contentType): self
    {
        // Generate Random Size [1KB, 4MB]
        $size = random_int(1_024, 4_194_304);

        // Generate Random Hash Based on Size
        $hash = hash('sha256', random_bytes($size));

        return new self($filePath, $hash, $contentType, $size, null, false, false);
    }

    public function equals(self $data, bool $strict = false): bool
    {
        if ($this->hash === $data->hash) {
            if (false === $strict) {
                return true;
            }

            return $this->filePath === $data->filePath;
        }

        return false;
    }

    public function toDataUri(): string
    {
        if (false === $contents = @file_get_contents($this->filePath)) {
            throw new EncodingFailedInvalidFilePathException($this->filePath);
        }

        return sprintf('data:%s;base64,%s', $this->contentType, base64_encode($contents));
    }
}
