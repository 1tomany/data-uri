<?php

namespace OneToMany\DataUri\Contract\Record;

interface SmartFileInterface
{
    /**
     * @return non-empty-string
     */
    public function getHash(): string;
}
