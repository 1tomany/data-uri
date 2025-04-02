<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Exception\EncodingDataFailedException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

final readonly class DataUri implements \Stringable
{
    public function __construct(
        public string $hash,
        public string $mediaType,
        public int $byteCount,
        public string $fileName,
        public string $filePath,
        public string $extension,
        public string $remoteKey,
    ) {
    }

    public function __destruct()
    {
        try {
            new Filesystem()->remove($this->filePath);
        } catch (IOExceptionInterface $e) {
        }
    }

    public function __toString(): string
    {
        return $this->filePath;
    }

    public function asUri(): string
    {
        try {
            $contents = new Filesystem()->readFile($this->filePath);
        } catch (IOExceptionInterface $e) {
            throw new EncodingDataFailedException($this->filePath, $e);
        }

        return sprintf('data:%s;base64,%s', $this->mediaType, \base64_encode($contents));
    }

    public function equals(self $data, bool $strict = false): bool
    {
        if ($this->hash === $data->hash) {
            if (false === $strict) {
                return true;
            }

            return $this->filePath === $data->filePath;
        }

        return false;
    }
}
