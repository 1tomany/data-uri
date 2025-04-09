<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;
use function substr;

final class ParsingFailedInvalidFilePathProvidedException extends \InvalidArgumentException implements ExceptionInterface
{
    public function __construct(string $filePath, ?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(sprintf('Parsing the data failed because the file path "%s" does not exist.', substr($filePath, 0, 1024)), $code, $previous);
    }
}
