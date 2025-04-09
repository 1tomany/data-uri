<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class EncodingFailedInvalidFilePathException extends \InvalidArgumentException implements ExceptionInterface
{
    public function __construct(string $filePath, ?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(sprintf('Encoding the data failed because the file "%s" does not exist.', $filePath), $code, $previous);
    }
}
