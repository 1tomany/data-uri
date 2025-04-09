<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class ProcessingFailedWritingTemporaryFileFailedException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $filePath, ?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(sprintf('Processing the data failed because an error occurred when attempting to write it to the temporary file "%s". Either the disk is full or the file no longer exists.', $filePath), $code, $previous);
    }
}
