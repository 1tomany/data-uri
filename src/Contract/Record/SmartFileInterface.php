<?php

namespace OneToMany\DataUri\Contract\Record;

use OneToMany\DataUri\Contract\Enum\FileType;
use OneToMany\DataUri\Exception\RuntimeException;

interface SmartFileInterface extends \Stringable
{
    /**
     * @var non-empty-string
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
     * @var ?non-empty-string
     */
    public ?string $extension { get; }

    public FileType $fileType { get; }

    /**
     * @var non-empty-string
     */
    public string $mimeType { get; }

    /**
     * @var int<0, max>
     */
    public int $size { get; }

    /**
     * @var non-empty-string
     */
    public string $remoteKey { get; }

    /**
     * If the object should delete the file it references when the destructor is called.
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

    public function equals(self $file, bool $strict = false): bool;

    public function exists(): bool;

    /**
     * @throws RuntimeException When reading the file fails
     */
    public function read(): string;

    public function shouldAutoDelete(): bool;

    /**
     * @throws RuntimeException When reading or encoding the file fails
     */
    public function toBase64(): string;

    /**
     * @return non-empty-string
     *
     * @throws RuntimeException When reading or encoding the file fails
     */
    public function toDataUri(): string;
}
