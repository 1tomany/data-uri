<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class ProcessingFailedTemporaryDirectoryNotWritableException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $tempDir, ?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(sprintf('Processing the data failed because the directory "%s" is not writable.', $tempDir), $code, $previous);
    }
}
