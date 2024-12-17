<?php

namespace OneToMany\DataUri;

final readonly class DataUri
{

    private function __construct(
        public string $data,
        public string $hash,
        public string $mimeType,
        public string $filename,
        public string $extension,
        public int $size,
    )
    {
    }

    public static function parse(?string $data): self
    {
        if (null === $data) {
            throw new \InvalidArgumentException('null file');
        }
    }

}
