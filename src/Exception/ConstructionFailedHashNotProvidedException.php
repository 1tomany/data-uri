<?php

namespace OneToMany\DataUri\Exception;

final class ConstructionFailedHashNotProvidedException extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Construction failed: non-empty hash is required.');
    }
}
