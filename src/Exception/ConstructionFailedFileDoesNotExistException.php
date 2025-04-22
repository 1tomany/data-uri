<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class ConstructionFailedFileDoesNotExistException extends InvalidArgumentException
{
    public function __construct(string $filePath, ?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(sprintf('Construction failed because the file "%s" does not exist.', $filePath), $code, $previous);
    }
}
