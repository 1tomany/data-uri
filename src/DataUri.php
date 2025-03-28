<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Exception\CreatingTemporaryFileFailedException;
use OneToMany\DataUri\Exception\DecodingDataFailedException;
use OneToMany\DataUri\Exception\ExceptionInterface;
use OneToMany\DataUri\Exception\GeneratingExtensionFailedException;
use OneToMany\DataUri\Exception\GeneratingHashFailedException;
use OneToMany\DataUri\Exception\GeneratingMimeTypeFailedException;
use OneToMany\DataUri\Exception\GeneratingSizeFailedException;
use OneToMany\DataUri\Exception\WritingTemporaryFileFailedException;

use function base64_decode;
use function base64_encode;
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
        public string $extension,
        public string $mimeType,
    )
    {
    }

    public function __destruct()
    {
        // if (is_file($this->path)) {
        //     unlink($this->path);
        // }
    }

    public function __toString(): string
    {
        return $this->path;
    }

    public function toUri(): string
    {
        return sprintf('data:%s;base64,%s', $this->mimeType, base64_encode($this->bytes));
    }

    public function toFile(): \SplFileInfo
    {
        return new \SplFileInfo($this->path);
    }

    public static function parseOrNull(?string $data, bool $deleteOriginal = true): ?self
    {
        try {
            return self::parse($data, $deleteOriginal);
        } catch (ExceptionInterface $e) { }

        return null;
    }

    public static function parse(?string $data, bool $deleteOriginal = true): self
    {
        $data = trim(strval($data));

        if (is_file($data) && is_readable($data)) {
            $bytes = @file_get_contents($data, false);

            // if ($deleteOriginal) {
            //     unlink($data);
            // }
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
            $tempPath = @tempnam(sys_get_temp_dir(), '__1n__datauri_');

            if (false === $tempPath || !@is_file($tempPath)) {
                throw new CreatingTemporaryFileFailedException(sys_get_temp_dir());
            }

            // Write Raw Bytes to Temporary File
            if (false === @file_put_contents($tempPath, $bytes)) {
                throw new WritingTemporaryFileFailedException();
            }

            // Resolve File Extension
            if (false === $extension = new \finfo(FILEINFO_EXTENSION)->file($tempPath)) {
                throw new GeneratingExtensionFailedException();
            }

            // Resolve Exact Extension
            $extension = explode('/', $extension)[0];

            // Resolve MIME Type
            if (false === $mimeType = mime_content_type($tempPath)) {
                throw new GeneratingMimeTypeFailedException();
            }

            $mimeType = trim(strtolower($mimeType));

            // Ensure the File Has an Extension
            if (in_array($extension, ['', '???'])) {
                $extension = explode('/', $mimeType)[1] ?? 'bin';
            }

            // Rename File With Extension
            $path = $tempPath . '.' . $extension;

            if (false === rename($tempPath, $path)) {
                throw new \Exception('no rename');
            }

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
            $name = self::generateName($hash, $extension);

            return new self(...[
                'bytes' => $bytes,
                'hash' => $hash,
                'name' => $name,
                'path' => $path,
                'size' => $size,
                'extension' => $extension,
                'mimeType' => $mimeType,
            ]);
        } catch (\Throwable $e) {
            self::cleanup($path);

            throw $e;
        }
    }

    private static function generateName(string $hash, string $extension): string
    {
        $nameParts = [];

        if ($directory = substr($hash, 0, 2)) {
            array_push($nameParts, $directory);
        }

        if ($directory = substr($hash, 2, 2)) {
            array_push($nameParts, $directory);
        }

        $nameParts[] = implode('.', array_filter([
            bin2hex(random_bytes(24)), $extension,
        ]));

        return implode('/', $nameParts);
    }

    private static function cleanup(?string $path): void
    {
        if (!$path) {
            return;
        }

        if (is_file($path)) {
            @unlink($path);
        }
    }

}
