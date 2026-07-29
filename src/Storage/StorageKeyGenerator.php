<?php

namespace OneToMany\DataUri\Storage;

use OneToMany\DataUri\Contract\HashableFileInterface;
use OneToMany\DataUri\Contract\Storage\StorageKeyGeneratorInterface;
use OneToMany\DataUri\Helper\FilenameHelper;

use function implode;
use function substr;
use function trim;

final readonly class StorageKeyGenerator implements StorageKeyGeneratorInterface
{
    public function __construct(
        private string $prefix = '',
    ) {
    }

    public function generate(HashableFileInterface $file): string
    {
        $hash = $file->getHash();
        $name = $file->getName();

        if (null === FilenameHelper::sanitize($file->getOriginalName())) {
            $name = $hash;

            if (null !== $extension = $file->getExtension()) {
                $name .= '.'.$extension;
            }
        }

        $bits = [
            substr($hash, 0, 2),
            substr($hash, 2, 2),
            $name,
        ];

        if ('' !== $prefix = trim($this->prefix, '/')) {
            array_unshift($bits, $prefix);
        }

        return implode('/', $bits);
    }
}
