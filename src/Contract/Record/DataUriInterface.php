<?php

namespace OneToMany\DataUri\Contract\Record;

use OneToMany\DataUri\Contract\Enum\Type;

interface DataUriInterface
{
    /**
     * @var non-empty-lowercase-string
     */
    public string $hash { get; }

    /**
     * @var non-empty-string
     */
    public string $path { get; }

    /**
     * @var non-empty-string
     */
    public string $name { get; }

    /**
     * @var non-negative-int
     */
    public int $size { get; }

    public Type $type { get; }

    /**
     * @var non-empty-string
     */
    public string $uri { get; }

    /**
     * @var ?non-empty-lowercase-string
     */
    public ?string $extension { get; }

    /**
     * @var non-empty-lowercase-string
     */
    public string $format { get; }
}
