<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class ConstructionFailedFileNotReadableException extends InvalidArgumentException
{
    public function __construct(string $filePath, ?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(sprintf('Construction failed because the file "%s" is not readable.', $filePath), $code, $previous);
    }
}
