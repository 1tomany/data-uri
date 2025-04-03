<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Exception\EncodingDataFailedException;
use OneToMany\DataUri\Exception\ExceptionInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property-read string $fileName
 */
final readonly class DataUri implements \Stringable
{
    public function __construct(
        public string $fingerprint,
        public string $mediaType,
        public int $byteCount,
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

    public function __get(string $name): string
    {
        if (\in_array($name, ['fileName'])) {
            return \basename($this->filePath);
        }

        throw new class(\sprintf('The property "%s" is invalid.', $name)) extends \InvalidArgumentException implements ExceptionInterface { };
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
            throw new EncodingDataFailedException($this->filePath, $e);
        }

        return \sprintf('data:%s;base64,%s', $this->mediaType, \base64_encode($contents));
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
