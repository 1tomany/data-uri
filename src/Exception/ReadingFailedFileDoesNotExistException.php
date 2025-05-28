<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class ReadingFailedFileDoesNotExistException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $filePath, ?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(sprintf('Reading the file "%s" failed because it does not exist or is not readable.', $filePath), $code, $previous);
    }
}
