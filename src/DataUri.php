<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Exception\CreatingTemporaryFileFailedException;
use OneToMany\DataUri\Exception\DecodingDataFailedException;
use OneToMany\DataUri\Exception\ExceptionInterface;
use OneToMany\DataUri\Exception\GeneratingExtensionFailedException;
use OneToMany\DataUri\Exception\GeneratingHashFailedException;
use OneToMany\DataUri\Exception\GeneratingMimeTypeFailedException;
use OneToMany\DataUri\Exception\GeneratingRandomFileNameFailedException;
use OneToMany\DataUri\Exception\GeneratingSizeFailedException;
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
use function strval;
use function substr;
use function sys_get_temp_dir;
use function trim;
use function unlink;
use const FILEINFO_EXTENSION;

final readonly class DataUri implements \Stringable
{

    private function __construct(
        public string $bytes,
        public string $hash,
        public string $name,
        public string $path,
        public int $size,
        public string $type,
        public string $mime,
    )
    {
    }

    public function __destruct()
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
    }

    public function __toString(): string
    {
        return $this->path;
    }

    public function toUri(): string
    {
        return sprintf('data:%s;base64,%s', $this->type, base64_encode($this->bytes));
    }

    public function toFile(): \SplFileInfo
    {
        return new \SplFileInfo($this->path);
    }

    public static function parseOrNull(?string $data, bool $deleteOriginal = true): ?self
    {
        try {
            return self::parse($data, $deleteOriginal);
        } catch (ExceptionInterface $e) {}

        return null;
    }

    public static function parse(?string $data, bool $deleteOriginal = true): self
    {
        $data = trim(strval($data));

        if (is_file($data) && is_readable($data)) {
            $bytes = file_get_contents($data);

            if ($deleteOriginal) {
                unlink($data);
            }
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

        $path = null;

        try {
            // Create Temporary File
            $path = @tempnam(sys_get_temp_dir(), '__1n__datauri_');

            if (false === $path || !is_file($path)) {
                throw new CreatingTemporaryFileFailedException(sys_get_temp_dir());
            }

            // Write Raw Bytes to Temporary File
            if (false === @file_put_contents($path, $bytes)) {
                throw new WritingTemporaryFileFailedException();
            }

            // Resolve File Extension
            if (false === $type = new \finfo(FILEINFO_EXTENSION)->file($path)) {
                throw new GeneratingExtensionFailedException();
            }

            // Resolve Exact Extension
            $type = strtolower(explode('/', $type)[0]);

            // Ensure the Extension is Alphanumeric
            if (!preg_match('/^[a-z0-9]+$/', $type)) {
                $type = 'unknown';
            }

            // Resolve MIME Type
            if (false === $mime = mime_content_type($path)) {
                throw new GeneratingMimeTypeFailedException();
            }

            $mime = trim(strtolower($mime));

            // Generate File Hash
            if (false === $hash = @sha1_file($path)) {
                throw new GeneratingHashFailedException();
            }

            // Calculate File Size
            clearstatcache(false, $path);

            if (false === $size = @filesize($path)) {
                throw new GeneratingSizeFailedException();
            }

            // Generate Random File Name
            $name = self::generateName($hash, $type);

            return new self(...[
                'bytes' => $bytes,
                'hash' => $hash,
                'name' => $name,
                'path' => $path,
                'size' => $size,
                'type' => $type,
                'mime' => $mime,
            ]);
        } catch (\Throwable $e) {
            if (is_file(strval($path))) {
                @unlink(strval($path));
            }

            throw $e;
        }
    }

    private static function generateName(string $hash, string $type): string
    {
        $name = bin2hex(random_bytes(24)) . ".{$type}";

        return implode('/', [substr($hash, 0, 2), substr($hash, 2, 2), $name]);
    }

}
