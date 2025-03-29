<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Exception\EncodingDataFailedException;

use function base64_encode;
use function file_get_contents;
use function is_file;
use function unlink;

final readonly class DataUri implements \Stringable
{

    public function __construct(
        public string $fingerprint,
        public string $mediaType,
        public int $byteCount,
        public string $fileName,
        public string $filePath,
        public string $extension,
        public string $remoteKey,
    )
    {
    }

    public function __destruct()
    {
        if (is_file($this->filePath)) {
            @unlink($this->filePath);
        }
    }

    public function __toString(): string
    {
        return $this->filePath;
    }

    public function asUri(): string
    {
        if (false === $contents = @file_get_contents($this->filePath)) {
            throw new EncodingDataFailedException($this->filePath);
        }

        return sprintf('data:%s;base64,%s', $this->mediaType, base64_encode($contents));
    }

}
