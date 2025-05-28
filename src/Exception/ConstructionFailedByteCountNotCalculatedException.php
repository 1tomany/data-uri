<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class ConstructionFailedByteCountNotCalculatedException extends InvalidArgumentException
{
    public function __construct(string $filePath, ?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(sprintf('Construction failed because the byte count of the file "%s" could not be calculated.', $filePath), $code, $previous);
    }
}
