<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class ConstructionFailedFileSizeNotReadableException extends InvalidArgumentException
{
    public function __construct(string $filePath, ?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(sprintf('Construction failed because the size of the file "%s" is not readable.', $filePath), $code, $previous);
    }
}
