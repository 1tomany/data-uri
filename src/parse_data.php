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
use Symfony\Component\Filesystem\Path;

use function base64_decode;
use function count;
use function explode;
use function file_exists;
use function filesize;
use function hash_algos;
use function in_array;
use function mime_content_type;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function stripos;
use function strlen;
use function strtolower;
use function strval;
use function substr;
use function trim;

use const FILEINFO_EXTENSION;
use const PHP_MAXPATHLEN;

function parse_data(
    ?string $data,
    ?string $tempDir = null,
    string $hashAlgorithm = 'sha256',
    bool $assumeBase64Data = false,
    bool $deleteOriginalFile = false,
    bool $selfDestruct = true,
    ?Filesystem $filesystem = null,
): SmartFile {
    if (!in_array($hashAlgorithm, hash_algos())) {
        throw new ParsingFailedInvalidHashAlgorithmProvidedException($hashAlgorithm);
    }

    $data = trim(strval($data));

    if (empty($data)) {
        throw new ParsingFailedEmptyDataProvidedException();
    }

    // $clientName = null;
    $dataUriBytes = $localFileBytes = $clientName = null;

    if ($assumeBase64Data && !str_starts_with($data, 'data:')) {
        $data = sprintf('data:application/octet-stream;base64,%s', $data);
    }

    // Attempt to match the RFC2397 scheme
    if (0 === stripos($data, 'data:') && str_contains($data, ',')) {
        // Remove "data:" prefix and split on comma
        $dataBits = explode(',', substr($data, 5));

        if (2 !== count($dataBits) || empty($dataBits[1])) {
            throw new ParsingFailedInvalidRfc2397EncodedDataException();
        }

        $dataUriBytes = trim($dataBits[1]);

        // Attempt to decode the data byte string with base64
        if (str_ends_with(strtolower($dataBits[0]), ';base64')) {
            if (!$dataUriBytes = base64_decode($dataBits[1], true)) {
                throw new ParsingFailedInvalidBase64EncodedDataException();
            }
        }

        unset($dataBits);
    }

    $filesystem ??= new Filesystem();

    if (null === $dataUriBytes) {
        try {
            if ($filesystem->exists($data) && \is_readable($data)) {
                $localFileBytes = $filesystem->readFile($data);
            }

            $clientName = \basename($data);
        } catch (IOExceptionInterface $e) {
            throw new ParsingFailedInvalidFilePathProvidedException($data, $e);
        } finally {
            if (true === $deleteOriginalFile) {
                $filesystem->remove($data);
            }
        }
    }

    if (null === $dataUriBytes && null === $localFileBytes) {
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

    $fileBytes = $dataUriBytes ?? $localFileBytes;

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

    return new SmartFile($filePath, $fingerprint, $mediaType, $clientName, $byteCount, true, $selfDestruct);
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
