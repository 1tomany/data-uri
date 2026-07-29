<?php

namespace OneToMany\DataUri\Contract\Storage;

use OneToMany\DataUri\Contract\HashableFileInterface;

interface StorageKeyGeneratorInterface
{
    /**
     * @return non-empty-string
     */
    public function generate(HashableFileInterface $file): string;
}
