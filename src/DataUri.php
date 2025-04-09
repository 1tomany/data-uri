<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Exception\EncodingFailedInvalidFilePathException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

use function base64_encode;
use function sprintf;

final readonly class DataUri implements \Stringable
{
    public function __construct(
        public string $fingerprint,
        public string $mediaType,
        public int $byteCount,
        public string $filePath,
        public string $fileName,
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

    public function toDataUri(): string
    {
        try {
            $contents = new Filesystem()->readFile($this->filePath);
        } catch (IOExceptionInterface $e) {
            throw new EncodingFailedInvalidFilePathException($this->filePath, $e);
        }

        return sprintf('data:%s;base64,%s', $this->mediaType, base64_encode($contents));
    }

    public function equals(self $data, bool $strict = false): bool
    {
        if ($this->fingerprint === $data->fingerprint) {
            if (false === $strict) {
                return true;
            }

            return $this->filePath === $data->filePath;
        }

        return false;
    }
}
