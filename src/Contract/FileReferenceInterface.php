<?php

namespace OneToMany\DataUri\Contract;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\MediaType;

interface FileReferenceInterface extends \Stringable
{
    /**
     * @return non-empty-string
     */
    public function getPath(): string;

    /**
     * Returns the safe display name used for the file.
     *
     * @return non-empty-string
     */
    public function getName(): string;

    /**
     * Returns the unmodified caller-supplied name when one was provided.
     */
    public function getOriginalName(): ?string;

    /**
     * @return non-negative-int
     */
    public function getSize(): int;

    public function getMediaType(): MediaType;

    /**
     * Returns the matching known type preset or Type::Other.
     */
    public function getType(): Type;

    /**
     * @return ?non-empty-lowercase-string
     */
    public function getExtension(): ?string;

    /**
     * @return non-empty-string
     */
    public function getFormat(): string;

    public function exists(): bool;
}
