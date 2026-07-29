<?php

namespace OneToMany\DataUri\Record;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\Record\DataUriInterface;
use OneToMany\DataUri\TemporaryFile;

/**
 * @deprecated use OneToMany\DataUri\TemporaryFile
 */
class DataUri extends TemporaryFile implements DataUriInterface
{
    /**
     * The legacy cleanup root is retained as metadata but is never recursively removed.
     */
    public readonly ?string $root;

    public function __construct(
        string $path,
        ?string $root,
        string $name,
        int $size,
        Type $type,
    ) {
        $this->root = $root;

        parent::__construct($path, $name, $size, $type, $name);
    }
}
