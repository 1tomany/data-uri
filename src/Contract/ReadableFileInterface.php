<?php

namespace OneToMany\DataUri\Contract;

use OneToMany\DataUri\Exception\RuntimeException;

interface ReadableFileInterface extends FileReferenceInterface
{
    /**
     * @return resource
     *
     * @throws RuntimeException when opening the file fails
     */
    public function open();

    /**
     * @throws RuntimeException when reading the file fails
     */
    public function read(): string;
}
