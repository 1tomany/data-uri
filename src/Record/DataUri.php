<?php

namespace OneToMany\DataUri\Record;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\Record\DataUriInterface;

class DataUri implements DataUriInterface
{
    /**
     * @param non-empty-lowercase-string $hash
     * @param non-empty-string $path
     * @param non-empty-string $name
     * @param non-negative-int $size
     * @param non-empty-string $uri
     */
    public function __construct(
        public readonly string $hash,
        public readonly string $path,
        public readonly string $name,
        public readonly int $size,
        public readonly Type $type,
        public readonly string $uri,
    ) {
    }

    public function __destruct()
    {
        if (\file_exists($this->path)) {
            @\unlink($this->path);
        }
    }

    /**
     * @var ?non-empty-lowercase-string
     */
    public ?string $extension {
        get => $this->type->getExtension();
    }

    /**
     * @var ?non-empty-lowercase-string
     */
    public ?string $format {
        get => $this->type->getFormat();
    }
}
