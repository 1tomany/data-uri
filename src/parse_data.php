<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Exception\ParsingFailedEmptyDataProvidedException;
use OneToMany\DataUri\Exception\ParsingFailedFilePathTooLongException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidBase64EncodedDataException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidFilePathProvidedException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidHashAlgorithmProvidedException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidRfc2397EncodedDataException;
use OneToMany\DataUri\Exception\ProcessingFailedCalculatingFileSizeFailedException;
use OneToMany\DataUri\Exception\ProcessingFailedGeneratingExtensionFailedException;
use OneToMany\DataUri\Exception\ProcessingFailedGeneratingHashFailedException;
use OneToMany\DataUri\Exception\ProcessingFailedRenamingTemporaryFileFailedException;
use OneToMany\DataUri\Exception\ProcessingFailedTemporaryDirectoryNotWritableException;
use OneToMany\DataUri\Exception\ProcessingFailedTemporaryFileNotWrittenException;
use OneToMany\DataUri\Exception\ProcessingFailedWritingTemporaryFileFailedException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function base64_decode;
use function basename;
use function count;
use function explode;
use function file_exists;
use function filesize;
use function finfo_file;
use function finfo_open;
use function hash_algos;
use function in_array;
use function is_writable;
use function mime_content_type;
use function parse_url;
use function rawurldecode;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function strlen;
use function strval;
use function substr;
use function trim;

use const FILEINFO_EXTENSION;
use const PHP_MAXPATHLEN;
use const PHP_URL_PATH;

function parse_data(
    ?string $data,
    ?string $tempDir = null,
    string $hashAlgorithm = 'sha256',
    ?string $displayName = null,
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

    $isTextData = false;

    // Variables that we will try to expand during parsing
    $contentType = $dataUriBytes = $localFileBytes = null;

    if ($assumeBase64Data && !str_starts_with($data, 'data:')) {
        $data = sprintf('data:application/octet-stream;base64,%s', $data);
    }

    // Parse the data based RFC2397: data:[<mediatype>][;base64],<data>
    if (str_starts_with($data, 'data:') && str_contains($data, ',')) {
        $dataBits = explode(',', substr($data, 5));

        if (2 !== count($dataBits)) {
            throw new ParsingFailedInvalidRfc2397EncodedDataException();
        }

        // Attempt to decode the base64 encoded data
        if (str_ends_with($dataBits[0], ';base64')) {
            if (false === $dataUriBytes = base64_decode($dataBits[1], true)) {
                throw new ParsingFailedInvalidBase64EncodedDataException();
            }
        }

        // Attempt to decode the URL encoded data
        if ($isTextData = null === $dataUriBytes) {
            $dataUriBytes = rawurldecode($dataBits[1]);
        }

        unset($dataBits);
    }

    $filesystem ??= new Filesystem();

    if (null === $dataUriBytes) {
        try {
            if (strlen($data) > PHP_MAXPATHLEN) {
                throw new ParsingFailedFilePathTooLongException();
            }

            $localFileBytes = $filesystem->readFile($data);
        } catch (IOExceptionInterface $e) {
            throw new ParsingFailedInvalidFilePathProvidedException($data, $e);
        } finally {
            if ($deleteOriginalFile) {
                _cleanup_safely($data);
            }
        }

        $displayName ??= basename(parse_url($data, PHP_URL_PATH) ?: '') ?: null;
    }

    // Ensure we have some raw data to work with
    $fileBytes = $dataUriBytes ?? $localFileBytes;

    // Ensure we can write the data to a temporary file
    if (!is_writable($tempDir ??= sys_get_temp_dir())) {
        throw new ProcessingFailedTemporaryDirectoryNotWritableException($tempDir);
    }

    try {
        // Create a temporary file with a unique prefix
        $tempPath = $filesystem->tempnam($tempDir, '__1n__datauri_');
    } catch (IOExceptionInterface $e) {
        throw new ProcessingFailedTemporaryFileNotWrittenException($tempDir, $e);
    }

    try {
        // Write the data to the temporary file
        $filesystem->dumpFile($tempPath, $fileBytes);
    } catch (IOExceptionInterface $e) {
        _cleanup_safely($tempPath, new ProcessingFailedWritingTemporaryFileFailedException($tempPath, $e));
    }

    // Determine the actual media type of the file
    if (false === $contentType = mime_content_type($tempPath)) {
        $contentType = $isTextData ? 'text/plain' : 'application/octet-stream';
    }

    $isTextData = in_array($contentType, [
        'text/plain',
    ]);

    // Resolve the extension based on the client file name or file contents
    $extension = Path::getExtension($displayName ?? '') ?: ($isTextData ? 'txt' : '');

    if (empty($extension)) {
        if (false !== $finfo = finfo_open(FILEINFO_EXTENSION)) {
            // Use finfo to inspect the file to determine the extension
            if (false === $extensions = finfo_file($finfo, $tempPath)) {
                _cleanup_safely($tempPath, new ProcessingFailedGeneratingExtensionFailedException($tempPath));
            }

            // @see https://www.php.net/manual/en/fileinfo.constants.php#constant.fileinfo-extension
            $extension = trim(explode('/', $extensions)[0], '? ');
        }
    }

    // Final attempt to determine the extension
    $extension = $extension ?: ($isTextData ? 'txt' : 'bin');

    try {
        // Generate path with the extension
        $filePath = $tempPath.'.'.$extension;

        // Rename the temporary file with the extension
        $filesystem->rename($tempPath, $filePath, true);
    } catch (IOExceptionInterface $e) {
        _cleanup_safely($tempPath, new ProcessingFailedRenamingTemporaryFileFailedException($tempPath, $filePath, $e));
    }

    try {
        // Generate a hash to uniquely identify this file
        $hash = hash($hashAlgorithm, $fileBytes, false);
    } catch (\ValueError $e) {
        _cleanup_safely($filePath, new ProcessingFailedGeneratingHashFailedException($filePath, $hashAlgorithm, $e));
    }

    // Calculate the filesize in bytes
    if (false === $byteCount = filesize($filePath)) {
        _cleanup_safely($filePath, new ProcessingFailedCalculatingFileSizeFailedException($filePath));
    }

    return new SmartFile($filePath, $hash, $contentType, $byteCount, $displayName, true, $selfDestruct);
}

/**
 * @return ($exception is not null ? never : void)
 */
function _cleanup_safely(string $filePath, ?\Throwable $exception = null): void
{
    if (file_exists($filePath)) {
        @unlink($filePath);
    }

    if (null !== $exception) {
        throw $exception;
    }
}
