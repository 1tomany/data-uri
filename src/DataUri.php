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
use function substr;
use function sys_get_temp_dir;
use function trim;
use function unlink;
use const FILEINFO_EXTENSION;
use const FILEINFO_MIME_TYPE;

final readonly class DataUri implements \Stringable
{

    private function __construct(
        public string $bytes,
        public string $hash,
        public string $name,
        public string $path,
        public int $size,
        public string $mimeType,
        public string $extension,
        public string $uri,
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
            $written = @file_put_contents($path, $bytes);

            if (false === $written) {
                throw new WritingTemporaryFileFailedException();
            }

            $info = new \finfo();

            // Generate File MIME Type
            if (false === $mimeType = $info->file($path, FILEINFO_MIME_TYPE)) {
                throw new GeneratingMimeTypeFailedException();
            }

            // Generate File Extension
            if (false === $extension = $info->file($path, FILEINFO_EXTENSION)) {
                throw new GeneratingExtensionFailedException();
            }

            // Resolve File Extension
            $extension = explode('/', $extension)[0];

            if (in_array($extension, ['', '???'])) {
                $extension = 'bin';
            }

            // Generate Random File Name
            $prefix = bin2hex(random_bytes(24));

            if (48 !== strlen($prefix)) {
                throw new GeneratingRandomFileNameFailedException();
            }

            $name = implode('.', [$prefix, $extension]);

            // Generate File Hash
            if (false === $hash = @sha1_file($path)) {
                throw new GeneratingHashFailedException();
            }

            // Calculate File Size
            clearstatcache(false, $path);

            if (false === $size = @filesize($path)) {
                throw new GeneratingSizeFailedException();
            }

            // Generate the Bucketed URI
            $dir1 = substr($hash, 0, 2);
            $dir2 = substr($hash, 2, 2);

            $uri = implode('/', [$dir1, $dir2, $name]);
        } catch (\Throwable $e) {
            if (is_file(strval($path))) {
                unlink(strval($path));
            }

            throw $e;
        }

        return new self($bytes, $hash, $name, $path, $size, $mimeType, $extension, $uri);
    }

}
