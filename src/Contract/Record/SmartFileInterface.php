<?php

namespace OneToMany\DataUri\Contract\Record;

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

    public function getDirectory(): string;

    public function getBasename(): string;

    /**
     * @return non-empty-string
     */
    public function getName(): string;

    /**
     * @return non-empty-string
     */
    public function getMimeType(): string;
}
