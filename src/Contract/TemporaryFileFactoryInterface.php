<?php

namespace OneToMany\DataUri\Contract;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\MediaType;

interface TemporaryFileFactoryInterface
{
    /**
     * @return positive-int
     */
    public function getMaximumBytes(): int;

    /**
     * @param resource $stream
     */
    public function createFromStream(
        mixed $stream,
        string|Type|MediaType|null $type = null,
        ?string $name = null,
        ?string $declaredType = null,
    ): TemporaryFileInterface;

    public function createFromString(
        string $contents,
        string|Type|MediaType|null $type = null,
        ?string $name = null,
    ): TemporaryFileInterface;
}
