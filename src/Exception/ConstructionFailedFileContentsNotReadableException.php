<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class ConstructionFailedFileContentsNotReadableException extends InvalidArgumentException
{
    public function __construct(string $filePath, ?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(sprintf('Construction failed because the contents of the file "%s" are not readable.', $filePath), $code, $previous);
    }
}
