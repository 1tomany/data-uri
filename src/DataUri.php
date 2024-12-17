<?php

namespace OneToMany\DataUri;

use finfo;

use function base64_decode;
use function explode;
use function file_get_contents;
use function file_put_contents;
// use function filesize;
use function is_file;
use function is_readable;
// use function mime_content_type;
// use function sha1_file;
use function substr;
use function sys_get_temp_dir;
use function unlink;
use const FILEINFO_EXTENSION;
use const FILEINFO_MIME_TYPE;

final readonly class DataUri
{

    private function __construct(
        public string $data,
        public string $hash,
        public string $mimeType,
        public string $filename,
        public ?string $extension = null,
        public int $size = 0,
    )
    {
    }

    public static function parse(?string $data): self
    {
        if (null === $data) {
            throw new \InvalidArgumentException('null file');
        }

        $bytes = null;

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

        if (!is_string($bytes)) {
            throw new \InvalidArgumentException('cant get bytes');
        }

        try {
            $pathname = @tempnam(sys_get_temp_dir(), '__1n__datauri_');

            if (false === $pathname || !is_file($pathname)) {
                throw new \RuntimeException('no path');
            }

            $written = @file_put_contents($pathname, $bytes);

            if (false === $written) {
                throw new \RuntimeException('no bytes written');
            }

            // $size = strlen($bytes);
            // $hash = sha1($bytes);
            $info = new finfo();

            if (false === $mimeType = $info->file($pathname, FILEINFO_MIME_TYPE)) {
                throw new \RuntimeException('no mime type');
            }

            if (false === $extension = $info->file($pathname, FILEINFO_EXTENSION)) {
                throw new \RuntimeException('no extension found');
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
                throw new \RuntimeException('no hash');
            }

            if (false === $size = @filesize($pathname)) {
                throw new \RuntimeException('no filesize');
            }
        } catch (\Throwable $e) {
            if (is_string($pathname)) {
                @unlink($pathname);
            }

            throw $e;
        }

        return new self($bytes, $hash, $mimeType, $filename, $extension, $size);
    }

}
