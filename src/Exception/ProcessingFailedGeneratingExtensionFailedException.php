<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class ProcessingFailedGeneratingExtensionFailedException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $filePath, ?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(sprintf('Processing the data failed because an extension for the file "%s" could not be generated.', $filePath), $code, $previous);
    }
}
