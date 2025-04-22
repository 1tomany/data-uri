<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Exception\ParsingFailedEmptyDataProvidedException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidBase64EncodedDataException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidDataProvidedException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidFilePathProvidedException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidHashAlgorithmProvidedException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidRfc2397EncodedDataException;
use OneToMany\DataUri\Exception\ProcessingFailedCalculatingFileSizeFailedException;
use OneToMany\DataUri\Exception\ProcessingFailedGeneratingExtensionFailedException;
use OneToMany\DataUri\Exception\ProcessingFailedGeneratingHashFailedException;
use OneToMany\DataUri\Exception\ProcessingFailedRenamingTemporaryFileFailedException;
use OneToMany\DataUri\Exception\ProcessingFailedTemporaryFileNotWrittenException;
use OneToMany\DataUri\Exception\ProcessingFailedWritingTemporaryFileFailedException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

use function array_shift;
use function base64_decode;
use function count;
use function explode;
use function file_exists;
use function filesize;
use function hash_algos;
use function in_array;
use function mime_content_type;
use function str_ends_with;
use function stripos;
use function strlen;
use function strtolower;
use function substr;
use function trim;

use const FILEINFO_EXTENSION;
use const PHP_MAXPATHLEN;

function parse_data(
    ?string $data,
    ?string $tempDir = null,
    string $hashAlgorithm = 'sha256',
    bool $selfDestruct = true,
    ?Filesystem $filesystem = null,
): SmartFile {
    if (!in_array($hashAlgorithm, hash_algos())) {
        throw new ParsingFailedInvalidHashAlgorithmProvidedException($hashAlgorithm);
    }

    $data = trim((string) $data);

    if (empty($data)) {
        throw new ParsingFailedEmptyDataProvidedException();
    }

    $fileBytes = null;

    // Match on the RFC2397 data URL scheme
    if (0 === stripos($data, 'data:')) {
        // Trim the prefix and split on the comma
        $bits = explode(',', substr($data, 5));

        if (2 !== count($bits)) {
            throw new ParsingFailedInvalidRfc2397EncodedDataException();
        }

        $mediaType = strtolower(
            trim(array_shift($bits))
        );

        $fileBytes = trim(array_shift($bits));

        // Attempt to decode the string with base64
        if (str_ends_with($mediaType, ';base64')) {
            $fileBytes = base64_decode($fileBytes);

            if (!$fileBytes) {
                throw new ParsingFailedInvalidBase64EncodedDataException();
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
        throw new ParsingFailedInvalidFilePathProvidedException($data, $e);
    }

    if (empty($fileBytes)) {
        throw new ParsingFailedInvalidDataProvidedException();
    }

    try {
        $tempDir ??= sys_get_temp_dir();

        // Regardless of the data source, attempt to create a temp
        // file to store the raw data. This allows us to inspect the
        // file itself determine actual the extension and media type.
        $tempPath = $filesystem->tempnam($tempDir, '__1n__datauri_');
    } catch (IOExceptionInterface $e) {
        throw new ProcessingFailedTemporaryFileNotWrittenException($tempDir, $e);
    }

    try {
        // Write the bytes to the temporary file
        $filesystem->dumpFile($tempPath, $fileBytes);
    } catch (IOExceptionInterface $e) {
        _cleanup_safely($tempPath, new ProcessingFailedWritingTemporaryFileFailedException($tempPath, $e));
    }

    // Use Fileinfo to inspect the actual file to determine the extension
    if (!$tempExtension = new \finfo(FILEINFO_EXTENSION)->file($tempPath)) {
        _cleanup_safely($tempPath, new ProcessingFailedGeneratingExtensionFailedException($tempPath));
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
        _cleanup_safely($tempPath, new ProcessingFailedRenamingTemporaryFileFailedException($tempPath, $filePath, $e));
    }

    try {
        // Generate a hash (or fingerprint) to allow the
        // user to determine if this file is unique or not
        $fingerprint = hash($hashAlgorithm, $fileBytes, false);
    } catch (\ValueError $e) {
        _cleanup_safely($filePath, new ProcessingFailedGeneratingHashFailedException($filePath, $hashAlgorithm, $e));
    }

    // Calculate the filesize in bytes
    if (false === $byteCount = filesize($filePath)) {
        _cleanup_safely($filePath, new ProcessingFailedCalculatingFileSizeFailedException($filePath));
    }

    return new SmartFile($filePath, $fingerprint, $mediaType, $byteCount, true, $selfDestruct);
}

function _cleanup_safely(string $filePath, \Throwable $exception): never
{
    if (strlen($filePath) <= PHP_MAXPATHLEN) {
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    throw $exception;
}
