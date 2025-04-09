<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class ProcessingFailedRenamingTemporaryFileFailedException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $fromPath, string $toPath, ?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(sprintf('Processing the data failed because an error occurred when attempting to rename the temporary file "%s" to "%s".', $fromPath, $toPath), $code, $previous);
    }
}
