<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Exception\EncodingDataFailedException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

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
    ) {
    }

    public function __destruct()
    {
        try {
            new Filesystem()->remove($this->path);
        } catch (IOExceptionInterface $e) {
        }
    }

    public function __toString(): string
    {
        return $this->path;
    }

    public function asUri(): string
    {
        try {
            $contents = new Filesystem()->readFile($this->path);
        } catch (IOExceptionInterface $e) {
            throw new EncodingDataFailedException($this->path, $e);
        }

        return sprintf('data:%s;base64,%s', $this->media, \base64_encode($contents));
    }

    public function equals(self $data, bool $strict = false): bool
    {
        if ($this->hash === $data->hash) {
            if (false === $strict) {
                return true;
            }

            return $this->path === $data->path;
        }

        return false;
    }

}
