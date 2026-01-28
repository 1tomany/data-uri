<?php

namespace OneToMany\DataUri\Contract\Record;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Exception\RuntimeException;

interface DataUriInterface extends \Stringable
{
    /**
     * Hashes must be at least four characters
     * so the remote key bucket can be generated.
     */
    public const int MINIMUM_HASH_LENGTH = 4;

    /**
     * @var non-empty-lowercase-string|\Closure
     */
    public string|\Closure $hash { get; }

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

    /**
     * @return non-empty-lowercase-string
     *
     * @throws RuntimeException when calculating the hash fails
     * @throws RuntimeException when the hash length is insufficient
     */
    public function getHash(): string;

    /**
     * @return non-empty-string
     */
    public function getPath(): string;

    /**
     * @return non-empty-string
     */
    public function getName(): string;

    /**
     * @return non-negative-int
     */
    public function getSize(): int;

    public function getType(): Type;

    /**
     * @return non-empty-string
     *
     * @throws RuntimeException when generating the URI fails
     */
    public function getUri(): string;

    /**
     * @return ?non-empty-lowercase-string
     */
    public function getExtension(): ?string;

    /**
     * @return non-empty-lowercase-string
     */
    public function getFormat(): string;

    /**
     * Determines if two `DataUriInterface` instances are equal.
     *
     * If the `$strict` argument is `false`, two objects are equal if their
     * hashes are identical. However, if the `$strict` argument is `true`,
     * the hash and path must be identical for the two objects to be equal.
     */
    public function equals(self $file, bool $strict = false): bool;

    /**
     * Determines if the file the object represents exists.
     */
    public function exists(): bool;

    /**
     * @throws RuntimeException when reading the file fails
     */
    public function read(): string;

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
