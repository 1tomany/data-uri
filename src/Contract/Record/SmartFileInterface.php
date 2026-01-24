<?php

namespace OneToMany\DataUri\Contract\Record;

use OneToMany\DataUri\Contract\Enum\FileType;
use OneToMany\DataUri\Exception\RuntimeException;

interface SmartFileInterface extends \Stringable
{
    /**
     * Hashes must be at least four characters
     * so the remote key bucket can be generated.
     */
    public const int MINIMUM_HASH_LENGTH = 4;

    /**
     * @var non-empty-lowercase-string
     */
    public string $hash { get; }

    /**
     * @var non-empty-string
     */
    public string $path { get; }

    /**
     * The display or client name of the file.
     *
     * @var non-empty-string
     */
    public string $name { get; }

    /**
     * The name of the file as it exists on the filesystem.
     */
    public string $basename { get; }

    /**
     * @var ?non-empty-lowercase-string
     */
    public ?string $extension { get; }

    public FileType $type { get; }

    /**
     * @var non-empty-lowercase-string
     */
    public string $format { get; }

    /**
     * @var non-negative-int
     */
    public int $size { get; }

    /**
     * @var non-empty-string
     */
    public string $remoteKey { get; }

    /**
     * If true, the file is deleted when the destructor is called.
     */
    public bool $autoDelete { get; }

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
     * @return ?non-empty-lowercase-string
     */
    public function getExtension(): ?string;

    public function getType(): FileType;

    /**
     * @return non-empty-lowercase-string
     */
    public function getFormat(): string;

    /**
     * @return non-negative-int
     */
    public function getSize(): int;

    /**
     * @return non-empty-string
     */
    public function getRemoteKey(): string;

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
     * @return non-empty-string
     *
     * @throws RuntimeException when reading or encoding the file fails
     */
    public function toDataUri(): string;
}
