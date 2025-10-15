<?php

namespace OneToMany\DataUri\Contract\Record;

use OneToMany\DataUri\Contract\Enum\FileType;

interface SmartFileInterface
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
}
