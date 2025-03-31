<?php

namespace OneToMany\DataUri
{

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
        string $hashAlgorithm = 'sha1',
        ?Filesystem $filesystem = null,
    ): DataUri
    {
        $data = \is_string($data) ? \trim($data) : null;

        if (empty($data)) {
            throw new DecodingDataFailedException();
        }

        if (!\in_array($hashAlgorithm, \hash_algos())) {
            throw new InvalidHashAlgorithmException($hashAlgorithm);
        }

        $bytes = null;

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

            $bytes = \trim($matches[3]);

            if ($isBase64Encoded && !empty($bytes)) {
                $bytes = \base64_decode($bytes);
            }
        }

        $filesystem ??= new Filesystem();

        try {
            if (empty($bytes) && $filesystem->exists($data)) {
                $bytes = $filesystem->readFile($data);
            }
        } catch (IOExceptionInterface $e) {
            throw new DecodingDataFailedException($e);
        }

        if (empty($bytes)) {
            throw new DecodingDataFailedException();
        }

        try {
            // Create Temporary File
            $tempDir ??= sys_get_temp_dir();

            $tempPath = $filesystem->tempnam(
                $tempDir, '__1n__datauri_'
            );
        } catch (IOExceptionInterface $e) {
            throw new CreatingTemporaryFileFailedException($tempDir, $e);
        }

        // Allows files to be deleted without error
        $cleanupSafely = function(?string $path): void {
            try {
                if ($path && \file_exists($path)) {
                    new Filesystem()->remove($path);
                }
            } catch (IOExceptionInterface $e) {}
        };

        try {
            try {
                // Write the bytes to the temporary file
                $filesystem->dumpFile($tempPath, $bytes);
            } catch (IOExceptionInterface $e) {
                throw new WritingTemporaryFileFailedException($tempPath, $e);
            }

            // Use Fileinfo to inspect the actual file to determine the extension
            if (false === $extension = new \finfo(\FILEINFO_EXTENSION)->file($tempPath)) {
                throw new GeneratingExtensionFailedException($tempPath);
            }

            // @see https://www.php.net/manual/en/fileinfo.constants.php#constant.fileinfo-extension
            if (true === \str_contains($extension, '/')) {
                $extension = \explode('/', $extension)[0];
            } else {
                $extension = 'bin';
            }

            try {
                // Generate path with the extension
                $filePath = Path::changeExtension(
                    $tempPath, $extension
                );

                // Rename the temporary file with the extension
                $filesystem->rename($tempPath, $filePath, true);
            } catch (IOExceptionInterface $e) {
                throw new RenamingTemporaryFileFailedException($tempPath, $filePath, $e);
            }
        } finally {
            $cleanupSafely($tempPath);
        }

        try {
            // Determine the actual media type of the file
            if (false === $media = \mime_content_type($tempPath)) {
                $media = 'application/octet-stream';
            }

            try {
                // Generate a hash of the raw bytes so the
                // end user can easily use it to determine
                // if this file exists in their system. The
                // hash will also be used to generate a
                // bucketed remote key so the file can be saved
                // on a remote filesystem like Amazon S3.
                $hash = hash($hashAlgorithm, $bytes, false);
            } catch (\ValueError $e) {
                throw new GeneratingHashFailedException($filePath, $hashAlgorithm, $e);
            }

            // Calculate File Size in Bytes
            if (false === $size = \filesize($filePath)) {
                throw new CalculatingFileSizeFailedException($filePath);
            }

            // Generate Bucketed Remote File Key
            $randomBytes = \bin2hex(\random_bytes(16));

            $key = \implode('.', [
                $randomBytes, $extension
            ]);

            if (!empty($prefix = \substr($hash, 2, 2))) {
                $key = $prefix . '/' . $key;
            }

            if (!empty($prefix = \substr($hash, 0, 2))) {
                $key = $prefix . '/' . $key;
            }
        } catch (\Throwable $e) {
            $cleanupSafely($filePath);

            throw $e;
        }

        return new DataUri($hash, $media, $size, \basename($filePath), $filePath, $extension, $key);
    }

}
