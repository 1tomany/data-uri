<?php

namespace OneToMany\DataUri\Contract\Record;

use OneToMany\DataUri\Contract\Enum\FileType;
use OneToMany\DataUri\Exception\RuntimeException;

interface SmartFileInterface extends \Stringable
{
    /**
     * @return non-empty-string
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

    public function getDirectory(): string;

    public function getBasename(): string;

    /**
     * @return ?non-empty-string
     */
    public function getExtension(): ?string;

    public function getFileType(): FileType;

    /**
     * @return non-empty-string
     */
    public function getMimeType(): string;

    /**
     * @return int<0, max>
     */
    public function getSize(): int;

    /**
     * @return non-empty-string
     */
    public function getRemoteKey(): string;

    public function shouldSelfDestruct(): bool;

    public function equals(self $file, bool $strict = false): bool;

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
     * @throws RuntimeException when reading or encoding the file fails
     */
    public function toDataUri(): string;
}
