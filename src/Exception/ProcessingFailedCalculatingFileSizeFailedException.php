<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class ProcessingFailedCalculatingFileSizeFailedException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $filePath)
    {
        parent::__construct(sprintf('Processing the data failed because the filesize for "%s" could not be calculated.', $filePath));
    }
}
