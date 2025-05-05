<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class ParsingFailedInvalidFilePathProvidedException extends \InvalidArgumentException implements ExceptionInterface
{
    public function __construct(string $filePath, ?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(sprintf('Parsing the data failed because the file "%s" does not exist or is not a file.', $filePath), $code, $previous);
    }
}
