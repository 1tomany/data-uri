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
        public string $hash,
        public string $media,
        public int $size,
        public string $name,
        public string $path,
        public string $ext,
        public string $key,
    )
    {
    }

    public function __destruct()
    {
        if (is_file($this->path)) {
            @unlink($this->path);
        }
    }

    public function __toString(): string
    {
        return $this->path;
    }

    public function asUri(): string
    {
        if (false === $contents = @file_get_contents($this->path)) {
            throw new EncodingDataFailedException($this->path);
        }

        return sprintf('data:%s;base64,%s', $this->media, base64_encode($contents));
    }

}
