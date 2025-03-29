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

    use function array_filter;
    use function array_shift;
    use function base64_decode;
    use function bin2hex;
    use function clearstatcache;
    use function explode;
    use function file_get_contents;
    use function file_put_contents;
    use function filesize;
    use function implode;
    use function is_file;
    use function is_readable;
    use function is_string;
    use function mime_content_type;
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

            $tempDir ??= sys_get_temp_dir();

            // Create Temporary File
            if (false === $tempPath = @tempnam($tempDir, '__1n__datauri_')) {
                throw new CreatingTemporaryFileFailedException(sys_get_temp_dir());
            }

            try {
                // Write Raw Bytes to Temporary File
                if (false === @file_put_contents($tempPath, $bytes)) {
                    throw new WritingTemporaryFileFailedException($tempPath);
                }

                // Resolve File Extension
                if (false === $ext = new \finfo(FILEINFO_EXTENSION)->file($tempPath)) {
                    throw new GeneratingExtensionFailedException($tempPath);
                }

                // Resolve Exact Extension
                if (str_contains($ext, '/')) {
                    /** @var list<non-empty-string> */
                    $extensionBits = array_filter(
                        explode('/', $ext)
                    );

                    /** @var non-empty-string $ext */
                    $ext = array_shift($extensionBits);
                }

                // Resolve Media Type
                if (false === $media = @mime_content_type($tempPath)) {
                    $media = 'application/octet-stream';
                }

                if ('???' === $ext) {
                    $ext = 'data';
                }

                // Rename File With Extension
                $filePath = $tempPath . '.' . $ext;

                if (false === rename($tempPath, $filePath)) {
                    throw new RenamingTemporaryFileFailedException($tempPath, $filePath);
                }
            } catch (\Throwable $e) {
                if (is_file($tempPath)) {
                    @unlink($tempPath);
                }

                throw $e;
            }

            try {
                // Generate SHA1 File Hash As Unique Fingerprint
                if (false === $hash = @sha1_file($filePath)) {
                    throw new GeneratingHashFailedException($filePath);
                }

                // Calculate File Size in Bytes
                clearstatcache(false, $filePath);

                if (false === $size = @filesize($filePath)) {
                    throw new GeneratingByteCountFailedException($filePath);
                }

                // Resolve File Name
                $name = basename($filePath);

                // Generate Bucketed Remote File Key
                $randomBytes = bin2hex(random_bytes(16));

                $key = implode('.', [
                    $randomBytes, $ext
                ]);

                if (!empty($prefix = substr($hash, 2, 2))) {
                    $key = $prefix . '/' . $key;
                }

                if (!empty($prefix = substr($hash, 0, 2))) {
                    $key = $prefix . '/' . $key;
                }
            } catch (\Throwable $e) {
                if (is_file($filePath)) {
                    @unlink($filePath);
                }

                throw $e;
            }

            return new DataUri($hash, $media, $size, $name, $filePath, $ext, $key);
        }
    }

}
