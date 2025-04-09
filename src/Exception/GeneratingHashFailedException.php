<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class GeneratingHashFailedException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $filePath, string $hashAlgorithm, ?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(sprintf('A hash for the file "%s" using the "%s" algorithm could not be generated.', $filePath, $hashAlgorithm), $code, $previous);
    }
}
