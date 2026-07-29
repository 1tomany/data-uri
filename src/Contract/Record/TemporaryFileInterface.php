<?php

namespace OneToMany\DataUri\Contract\Record;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Exception\RuntimeException;

interface TemporaryFileInterface extends \Stringable
{
    /**
     * Minimum hash length to ensure a key can be generated.
     *
     * @var positive-int
     */
    public const int MINIMUM_HASH_LENGTH = 4;

    /**
     * @return non-empty-string
     */
    public function getPath(): string;

    /**
     * @return ?non-empty-string
     */
    public function getRoot(): ?string;

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
     * @return ?non-empty-lowercase-string
     */
    public function getExtension(): ?string;

    /**
     * @return non-empty-lowercase-string
     */
    public function getFormat(): string;

    /**
     * @return non-empty-lowercase-string
     */
    public function getHash(): string;

    /**
     * @return non-empty-string
     */
    public function getKey(): string;

    /**
     * Determines if two instances are equal.
     *
     * @param bool $strict If true, each object must represent the same file.
     *                     If false, each object must have an identical hash.
     */
    public function equals(self $file, bool $strict = false): bool;

    /**
     * Determines if the file the object represents exists.
     */
    public function exists(): bool;

    /**
     * Reads the entire file into memory.
     *
     * @throws RuntimeException when reading the file fails
     */
    public function read(): string;

    /**
     * Returns the base64 encoding of the file.
     *
     * @throws RuntimeException when reading or encoding the file fails
     */
    public function toBase64(): string;

    /**
     * Returns the contents of file as a data URI as defined in RFC 2397.
     *
     * @see https://www.rfc-editor.org/rfc/rfc2397.html
     *
     * @return non-empty-string
     *
     * @throws RuntimeException when reading or encoding the file fails
     */
    public function toDataUri(): string;

    /**
     * Deletes all files and directories represented by this object.
     *
     * @throws RuntimeException when any part of the deletion process fails
     */
    public function delete(): void;

    /**
     * Transfers cleanup responsibility to the caller and returns the path.
     *
     * @return non-empty-string
     */
    public function detach(): string;

    /**
     * If true, all files and directories managed by the object will
     * be deleted when the destructor is called. If false, the caller
     * is responsible for deleting any data referenced by this object.
     */
    public function isManaged(): bool;
}
