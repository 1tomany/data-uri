<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Exception\ConstructionFailedFileContentsNotReadableException;
use OneToMany\DataUri\Exception\ConstructionFailedFileDoesNotExistException;
use OneToMany\DataUri\Exception\ConstructionFailedFileNotFileException;
use OneToMany\DataUri\Exception\ConstructionFailedFileNotReadableException;
use OneToMany\DataUri\Exception\ConstructionFailedFilePathNotProvidedException;
use OneToMany\DataUri\Exception\ConstructionFailedFileSizeNotProvidedException;
use OneToMany\DataUri\Exception\ConstructionFailedFileSizeNotReadableException;
use OneToMany\DataUri\Exception\ConstructionFailedFingerprintNotProvidedException;
use OneToMany\DataUri\Exception\ConstructionFailedMediaTypeNotProvidedException;
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
use function sprintf;
use function strtolower;
use function substr;
use function trim;
use function unlink;
use function vsprintf;

use const PATHINFO_EXTENSION;

readonly class SmartFile implements \Stringable
{
    public string $fingerprint;
    public string $mediaType;
    public int $byteCount;
    public string $filePath;
    public string $fileName;
    public string $extension;
    public string $remoteKey;
    public bool $selfDestruct;

    public function __construct(
        string $filePath,
        ?string $fingerprint,
        string $mediaType,
        ?int $byteCount = null,
        bool $checkExists = true,
        bool $selfDestruct = true,
    ) {
        $filePath = trim($filePath);

        if (empty($filePath)) {
            throw new ConstructionFailedFilePathNotProvidedException();
        }

        $this->filePath = $filePath;

        if ($checkExists) {
            if (!file_exists($this->filePath)) {
                throw new ConstructionFailedFileDoesNotExistException($this->filePath);
            }

            if (!is_readable($this->filePath)) {
                throw new ConstructionFailedFileNotReadableException($this->filePath);
            }

            if (!is_file($this->filePath)) {
                throw new ConstructionFailedFileNotFileException($this->filePath);
            }

            if (null === $fingerprint) {
                if (false === $contents = @file_get_contents($this->filePath)) {
                    throw new ConstructionFailedFileContentsNotReadableException($this->filePath);
                }

                $fingerprint = hash('sha256', $contents);
            }

            if (false === $byteCount = ($byteCount ?? filesize($this->filePath))) {
                throw new ConstructionFailedFileSizeNotReadableException($this->filePath);
            }
        }

        $this->fileName = basename($this->filePath);

        if (null === $fingerprint) {
            throw new ConstructionFailedFingerprintNotProvidedException($this->filePath);
        }

        $this->fingerprint = $fingerprint;

        if (null === $byteCount) {
            throw new ConstructionFailedFileSizeNotProvidedException($this->filePath);
        }

        $this->byteCount = $byteCount;

        if (empty($mediaType)) {
            throw new ConstructionFailedMediaTypeNotProvidedException($this->filePath);
        }

        $this->mediaType = strtolower($mediaType);

        // Resolve the File's Extension
        $this->extension = pathinfo($this->filePath, PATHINFO_EXTENSION);

        // Generate the Remote Key
        $remoteFileKeyPrefix = vsprintf('%s.%s', [
            $this->fingerprint, $this->extension,
        ]);

        $remoteKeyBits = [$remoteFileKeyPrefix];

        if ($prefix = substr($fingerprint, 2, 2)) {
            array_unshift($remoteKeyBits, $prefix);
        }

        if ($prefix = substr($fingerprint, 0, 2)) {
            array_unshift($remoteKeyBits, $prefix);
        }

        $this->remoteKey = implode(
            '/', $remoteKeyBits
        );

        $this->selfDestruct = $selfDestruct;
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

    public function toDataUri(): string
    {
        if (false === $contents = @file_get_contents($this->filePath)) {
            throw new EncodingFailedInvalidFilePathException($this->filePath);
        }

        return sprintf('data:%s;base64,%s', $this->mediaType, base64_encode($contents));
    }

    public function equals(self $data, bool $strict = false): bool
    {
        if ($this->fingerprint === $data->fingerprint) {
            if (false === $strict) {
                return true;
            }

            return $this->filePath === $data->filePath;
        }

        return false;
    }
}
