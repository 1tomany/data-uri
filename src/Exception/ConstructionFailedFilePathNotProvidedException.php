<?php

namespace OneToMany\DataUri\Exception;

final class ConstructionFailedFilePathNotProvidedException extends InvalidArgumentException
{
    public function __construct(?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct('Construction failed because an empty file path was provided.', $code, $previous);
    }
}
