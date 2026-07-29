<?php

namespace OneToMany\DataUri\Contract;

use OneToMany\DataUri\Exception\RuntimeException;

interface EncodableFileInterface extends ReadableFileInterface
{
    /**
     * @throws RuntimeException when reading or encoding the file fails
     */
    public function toBase64(): string;

    /**
     * @return non-empty-string
     *
     * @throws RuntimeException when reading or encoding the file fails
     */
    public function toDataUri(): string;
}
