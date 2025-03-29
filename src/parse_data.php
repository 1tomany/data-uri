<?php

namespace OneToMany\DataUri
{

    use OneToMany\DataUri\Exception\CreatingTemporaryFileFailedException;
    use OneToMany\DataUri\Exception\DecodingDataFailedException;
    use OneToMany\DataUri\Exception\GeneratingExtensionFailedException;
    use OneToMany\DataUri\Exception\GeneratingHashFailedException;
    use OneToMany\DataUri\Exception\GeneratingByteCountFailedException;
    use OneToMany\DataUri\Exception\RenamingTemporaryFileFailedException;
    use OneToMany\DataUri\Exception\WritingTemporaryFileFailedException;

    use function base64_decode;
    use function bin2hex;
    use function explode;
    use function file_get_contents;
    use function file_put_contents;
    use function filesize;
    use function implode;
    use function is_file;
    use function is_readable;
    use function random_bytes;
    use function sha1_file;
    use function str_starts_with;
    use function strval;
    use function substr;
    use function sys_get_temp_dir;
    use function tempnam;
    use function trim;
    use function unlink;
    use const FILEINFO_EXTENSION;

    if (!function_exists('\OneToMany\DataUri\parse_data')) {
        function parse_data(?string $data, ?string $tempDir = null): DataUri
        {
            $data = trim(strval($data));

            if (is_file($data) && is_readable($data)) {
                $bytes = @file_get_contents($data, false);
            }

            if (str_starts_with($data, 'data:')) {
                // Remove 'data:' Prefix and Break Apart
                $bits = explode(',', substr($data, 5), 2);

                // Decode to Binary Representation
                if (null !== ($bits[1] ?? null)) {
                    $bytes = base64_decode($bits[1]);
                }
            }

            if (!isset($bytes) || !is_string($bytes)) {
                throw new DecodingDataFailedException();
            }

            $filePath = $tempPath = null;

            try {
                $tempDir ??= sys_get_temp_dir();

                // Create Temporary File
                $tempPath = @tempnam($tempDir, '__1n__datauri_');

                if (false === $tempPath || !is_file($tempPath)) {
                    throw new CreatingTemporaryFileFailedException($tempDir);
                }

                // Write Raw Bytes to Temporary File
                if (false === @file_put_contents($tempPath, $bytes)) {
                    throw new WritingTemporaryFileFailedException($tempPath);
                }

                // Resolve File Extension
                if (false === $extension = new \finfo(FILEINFO_EXTENSION)->file($tempPath)) {
                    throw new GeneratingExtensionFailedException($tempPath);
                }

                // Resolve Exact Extension
                if (str_contains($extension, '/')) {
                    /** @var list<non-empty-string> */
                    $extensionBits = array_filter(
                        explode('/', $extension)
                    );

                    /** @var non-empty-string $extension */
                    $extension = array_shift($extensionBits);
                }

                // Resolve Media Type
                if (false === $mediaType = @mime_content_type($tempPath)) {
                    $mediaType = 'application/octet-stream';
                }

                if ('???' === $extension) {
                    $extension = 'data';
                }

                // Rename File With Extension
                $filePath = $tempPath . '.' . $extension;

                if (false === rename($tempPath, $filePath)) {
                    throw new RenamingTemporaryFileFailedException($tempPath, $filePath);
                }

                // Generate SHA1 File Hash
                if (false === $fileHash = @sha1_file($filePath)) {
                    throw new GeneratingHashFailedException($filePath);
                }

                // Calculate File Size in Bytes
                clearstatcache(false, $filePath);

                if (false === $byteCount = @filesize($filePath)) {
                    throw new GeneratingByteCountFailedException($filePath);
                }

                // Resolve Base File Name
                $fileName = basename($filePath);

                // Generate Random Bucketed File Key
                $randomBytes = bin2hex(random_bytes(16));

                $remoteKey = implode('.', [
                    $randomBytes, $extension
                ]);

                if (!empty($prefix = substr($fileHash, 2, 2))) {
                    $remoteKey = $prefix . '/' . $remoteKey;
                }

                if (!empty($prefix = substr($fileHash, 0, 2))) {
                    $remoteKey = $prefix . '/' . $remoteKey;
                }

                return new DataUri(...[
                    'fileHash' => $fileHash,
                    'mediaType' => $mediaType,
                    'byteCount' => $byteCount,
                    'fileName' => $fileName,
                    'filePath' => $filePath,
                    'extension' => $extension,
                    'remoteKey' => $remoteKey,
                ]);
            } catch (\Throwable $e) {
                if (is_string($filePath)) {
                    @unlink($filePath);
                }

                if (is_string($tempPath)) {
                    @unlink($tempPath);
                }

                throw $e;
            }
        }
    }

}
