<?php

namespace OneToMany\DataUri;

use finfo;
use OneToMany\DataUri\Exception\CreatingTemporaryFileFailedException;
use OneToMany\DataUri\Exception\DecodingDataFailedException;
use OneToMany\DataUri\Exception\ExceptionInterface;
use OneToMany\DataUri\Exception\GeneratingExtensionFailedException;
use OneToMany\DataUri\Exception\GeneratingHashFailedException;
use OneToMany\DataUri\Exception\GeneratingMimeTypeFailedException;
use OneToMany\DataUri\Exception\GeneratingSizeFailedException;
use OneToMany\DataUri\Exception\WritingTemporaryFileFailedException;

use function base64_decode;
use function bin2hex;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function filesize;
use function is_file;
use function is_readable;
use function random_bytes;
use function sha1_file;
use function substr;
use function sys_get_temp_dir;
use function trim;
use function unlink;
use function vsprintf;
use const FILEINFO_EXTENSION;
use const FILEINFO_MIME_TYPE;

final readonly class DataUri
{

    private function __construct(
        public string $data,
        public string $hash,
        public string $mimeType,
        public string $filename,
        public string $pathname,
        public ?string $extension = null,
        public int $size = 0,
    )
    {
    }

    public static function parseOrNull(?string $data): ?self
    {
        try {
            return self::parse($data);
        } catch (ExceptionInterface $e) { }

        return null;
    }

    public static function parse(?string $data): self
    {
        $data = trim((string)$data);

        if (is_file($data) && is_readable($data)) {
            $bytes = @file_get_contents($data);
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

        $pathname = null;

        try {
            $pathname = @tempnam(sys_get_temp_dir(), '__1n__datauri_');

            if (false === $pathname || !is_file($pathname)) {
                throw new CreatingTemporaryFileFailedException(sys_get_temp_dir());
            }

            $written = @file_put_contents($pathname, $bytes);

            if (false === $written) {
                throw new WritingTemporaryFileFailedException();
            }

            $info = new finfo();

            if (false === $mimeType = $info->file($pathname, FILEINFO_MIME_TYPE)) {
                throw new GeneratingMimeTypeFailedException();
            }

            if (false === $extension = $info->file($pathname, FILEINFO_EXTENSION)) {
                throw new GeneratingExtensionFailedException();
            }

            $extension = explode('/', $extension)[0];

            if ('???' === $extension) {
                $extension = null;
            }

            $filename = bin2hex(random_bytes(24));

            if (null !== $extension) {
                $filename = vsprintf('%s.%s', [
                    $filename, $extension
                ]);
            }

            if (false === $hash = @sha1_file($pathname)) {
                throw new GeneratingHashFailedException();
            }

            if (false === $size = @filesize($pathname)) {
                throw new GeneratingSizeFailedException();
            }
        } catch (\Throwable $e) {
            if (is_file((string)$pathname)) {
                @unlink((string)$pathname);
            }

            throw $e;
        }

        return new self($bytes, $hash, $mimeType, $filename, $pathname, $extension, $size);
    }

}
