<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Exception\CalculatingFileSizeFailedException;
use OneToMany\DataUri\Exception\CreatingTemporaryFileFailedException;
use OneToMany\DataUri\Exception\DecodingDataFailedException;
use OneToMany\DataUri\Exception\EmptyDataProvidedException;
use OneToMany\DataUri\Exception\GeneratingExtensionFailedException;
use OneToMany\DataUri\Exception\GeneratingHashFailedException;
use OneToMany\DataUri\Exception\InvalidBase64EncodedDataUriException;
use OneToMany\DataUri\Exception\InvalidFilePathException;
use OneToMany\DataUri\Exception\InvalidHashAlgorithmException;
use OneToMany\DataUri\Exception\InvalidRfc2397EncodedDataUriException;
use OneToMany\DataUri\Exception\RenamingTemporaryFileFailedException;
use OneToMany\DataUri\Exception\WritingTemporaryFileFailedException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

use function array_shift;
use function base64_decode;
use function basename;
use function bin2hex;
use function count;
use function explode;
use function file_exists;
use function filesize;
use function hash_algos;
use function in_array;
use function mime_content_type;
use function random_bytes;
use function str_ends_with;
use function stripos;
use function strtolower;
use function substr;
use function trim;

use const FILEINFO_EXTENSION;

function parse_data(
    ?string $data,
    ?string $tempDir = null,
    string $hashAlgorithm = 'sha256',
    ?Filesystem $filesystem = null,
): DataUri {
    $data = trim((string) $data);

    if (empty($data)) {
        throw new EmptyDataProvidedException();
    }

    if (!in_array($hashAlgorithm, hash_algos())) {
        throw new InvalidHashAlgorithmException($hashAlgorithm);
    }

    $fileBytes = null;

    // Match on the RFC2397 data URL scheme
    if (0 === stripos($data, 'data:')) {
        // Trim the prefix and split on the comma
        $bits = explode(',', substr($data, 5));

        if (2 !== count($bits)) {
            throw new InvalidRfc2397EncodedDataUriException();
        }

        $mediaType = strtolower(
            trim(array_shift($bits))
        );

        $fileBytes = trim(array_shift($bits));

        // Attempt to decode the string with base64
        if (str_ends_with($mediaType, ';base64')) {
            $fileBytes = base64_decode($fileBytes);

            if (!$fileBytes) {
                throw new InvalidBase64EncodedDataUriException();
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
        throw new InvalidFilePathException($e);
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
    if (!$tempExtension = new \finfo(FILEINFO_EXTENSION)->file($tempPath)) {
        _cleanup_safely($tempPath, new GeneratingExtensionFailedException($tempPath));
    }

    // @see https://www.php.net/manual/en/fileinfo.constants.php#constant.fileinfo-extension
    $extension = explode('/', strtolower($tempExtension))[0];

    if ('???' === $extension) {
        $extension = 'bin';
    }

    // Determine the actual media type of the file
    if (false === $mediaType = mime_content_type($tempPath)) {
        $mediaType = 'application/octet-stream';
    }

    try {
        // Generate path with the extension
        $filePath = $tempPath.'.'.$extension;

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
    if (false === $byteCount = filesize($filePath)) {
        _cleanup_safely($filePath, new CalculatingFileSizeFailedException($filePath));
    }

    // Generate a random name for the remote key
    $remoteKey = bin2hex(random_bytes(16)).'.'.$extension;

    if (!empty($prefix = substr($fingerprint, 2, 2))) {
        $remoteKey = $prefix.'/'.$remoteKey;
    }

    if (!empty($prefix = substr($fingerprint, 0, 2))) {
        $remoteKey = $prefix.'/'.$remoteKey;
    }

    return new DataUri($fingerprint, $mediaType, $byteCount, $filePath, basename($filePath), $extension, $remoteKey);
}

function _cleanup_safely(string $filePath, \Throwable $exception): never
{
    if (file_exists($filePath)) {
        @unlink($filePath);
    }

    throw $exception;
}
