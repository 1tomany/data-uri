<?php

namespace OneToMany\DataUri\Contract;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\MediaType;

interface MediaTypeResolverInterface
{
    /**
     * @param non-empty-string $path
     */
    public function resolve(
        string $path,
        string|Type|MediaType|null $preferred = null,
        ?string $declared = null,
    ): MediaType;
}
