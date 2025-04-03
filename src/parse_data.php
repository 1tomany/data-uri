<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Exception\CalculatingFileSizeFailedException;
use OneToMany\DataUri\Exception\CreatingTemporaryFileFailedException;
use OneToMany\DataUri\Exception\DecodingDataFailedException;
use OneToMany\DataUri\Exception\GeneratingExtensionFailedException;
use OneToMany\DataUri\Exception\GeneratingHashFailedException;
use OneToMany\DataUri\Exception\InvalidHashAlgorithmException;
use OneToMany\DataUri\Exception\RenamingTemporaryFileFailedException;
use OneToMany\DataUri\Exception\WritingTemporaryFileFailedException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

function parse_data(
    ?string $data,
    ?string $tempDir = null,
    string $hashAlgorithm = 'sha256',
    ?Filesystem $filesystem = null,
): DataUri {
    $data = \is_string($data) ? \trim($data) : null;

    if (empty($data)) {
        throw new DecodingDataFailedException();
    }

    if (!\in_array($hashAlgorithm, \hash_algos())) {
        throw new InvalidHashAlgorithmException($hashAlgorithm);
    }

    $fileBytes = null;

    // Loosely attempt to match on the RFC 2937 Data URI scheme
    if (\preg_match('/^(data:)(.+)?,(.+)$/i', $data, $matches)) {
        $isBase64Encoded = false;

        if (!empty($matches[2]) && \str_contains($matches[2], ';')) {
            $mediaTypeBits = \explode(';', \strtolower($matches[2]));

            /** @var ?non-empty-string $encodingType */
            $encodingType = \array_pop($mediaTypeBits);

            if ('base64' === $encodingType) {
                $isBase64Encoded = true;
            }
        }

        $fileBytes = \trim($matches[3]);

        if ($isBase64Encoded && !empty($fileBytes)) {
            $fileBytes = \base64_decode($fileBytes);

            if (false === $fileBytes || empty($fileBytes)) {
                throw new DecodingDataFailedException();
            }
        }
    }

    $filesystem ??= new Filesystem();

    try {
        // If the data was not encoded as a URI, check
        // to see if it is a path to an existing file.
        if (empty($fileBytes) && $filesystem->exists($data)) {
            $fileBytes = $filesystem->readFile($data);
        }
    } catch (IOExceptionInterface $e) {
        throw new DecodingDataFailedException($e);
    }

    if (empty($fileBytes)) {
        throw new DecodingDataFailedException();
    }

    try {
        $tempDir ??= sys_get_temp_dir();

        // Regardless of the data source, attempt to create a temp
        // file to store the raw data. This allows us to inspect the
        // file itself determine actual the extension and media type.
        $tempPath = $filesystem->tempnam($tempDir, '__1n__datauri_');
    } catch (IOExceptionInterface $e) {
        throw new CreatingTemporaryFileFailedException($tempDir, $e);
    }

    try {
        // Write the bytes to the temporary file
        $filesystem->dumpFile($tempPath, $fileBytes);
    } catch (IOExceptionInterface $e) {
        _cleanup_safely($tempPath, new WritingTemporaryFileFailedException($tempPath, $e));
    }

    // Use Fileinfo to inspect the actual file to determine the extension
    if (!$tempExtension = new \finfo(\FILEINFO_EXTENSION)->file($tempPath)) {
        _cleanup_safely($tempPath, new GeneratingExtensionFailedException($tempPath));
    }

    // @see https://www.php.net/manual/en/fileinfo.constants.php#constant.fileinfo-extension
    $extension = \explode('/', \strtolower($tempExtension))[0];

    if ('???' === $extension) {
        $extension = 'bin';
    }

    // Determine the actual media type of the file
    if (false === $mediaType = \mime_content_type($tempPath)) {
        $mediaType = 'application/octet-stream';
    }

    try {
        // Generate path with the extension
        $filePath = $tempPath.'.'.$extension;
        // $fileName = \basename($filePath);

        // Rename the temporary file with the extension
        $filesystem->rename($tempPath, $filePath, true);
    } catch (IOExceptionInterface $e) {
        _cleanup_safely($tempPath, new RenamingTemporaryFileFailedException($tempPath, $filePath, $e));
    }

    try {
        // Generate a hash (or fingerprint) to allow the
        // user to determine if this file is unique or not
        $fingerprint = hash($hashAlgorithm, $fileBytes, false);
    } catch (\ValueError $e) {
        _cleanup_safely($filePath, new GeneratingHashFailedException($filePath, $hashAlgorithm, $e));
    }

    // Calculate the filesize in bytes
    if (false === $byteCount = \filesize($filePath)) {
        _cleanup_safely($filePath, new CalculatingFileSizeFailedException($filePath));
    }

    // Generate a random name for the remote key
    $remoteKey = \bin2hex(\random_bytes(16)).'.'.$extension;

    if (!empty($prefix = \substr($fingerprint, 2, 2))) {
        $remoteKey = $prefix.'/'.$remoteKey;
    }

    if (!empty($prefix = \substr($fingerprint, 0, 2))) {
        $remoteKey = $prefix.'/'.$remoteKey;
    }

    return new DataUri($fingerprint, $mediaType, $byteCount, $filePath, $extension, $remoteKey);
}

function _cleanup_safely(string $filePath, \Throwable $exception): never
{
    if (\file_exists($filePath)) {
        @\unlink($filePath);
    }

    throw $exception;
}
