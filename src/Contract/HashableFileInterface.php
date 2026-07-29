<?php

namespace OneToMany\DataUri\Contract;

use OneToMany\DataUri\Exception\RuntimeException;

interface HashableFileInterface extends FileReferenceInterface
{
    public const int MINIMUM_HASH_LENGTH = 4;

    /**
     * @return non-empty-lowercase-string
     *
     * @throws RuntimeException when generating the hash fails
     */
    public function getHash(): string;
}
