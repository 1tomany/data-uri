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

    /**
     * @return non-empty-string
     */
    public function getBasename(): string;

    /**
     * @return non-empty-string
     */
    public function getName(): string;
}
