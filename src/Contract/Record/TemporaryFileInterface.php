<?php

namespace OneToMany\DataUri\Contract\Record;

use OneToMany\DataUri\Contract\Enum\FileType;
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
    public function getBase(): ?string;

    /**
     * @return non-empty-string
     */
    public function getName(): string;

    /**
     * @return non-negative-int
     */
    public function getSize(): int;

    public function getType(): FileType;

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
     * Determines if two instances have identical hashes.
     */
    public function isEqual(self $file): bool;

    /**
     * Determines if two instances have identical hashes and paths.
     */
    public function isSame(self $file): bool;

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
     * Transfers cleanup responsibility to the caller.
     */
    public function detach(): self;

    /**
     * If true, all files and directories managed by the object will
     * be deleted when the destructor is called. If false, the caller
     * is responsible for deleting any data referenced by this object.
     */
    public function isManaged(): bool;
}
