<?php

namespace OneToMany\DataUri\Exception;

final class RenamingTemporaryFileFailedException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $fromPath, string $toPath, ?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(\sprintf('An error occurred when attempting to rename the temporary file "%s" to "%s".', $fromPath, $toPath), $code, $previous);
    }
}
