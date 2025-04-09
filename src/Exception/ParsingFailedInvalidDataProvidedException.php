<?php

namespace OneToMany\DataUri\Exception;

final class ParsingFailedInvalidDataProvidedException extends \InvalidArgumentException implements ExceptionInterface
{
    public function __construct(?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct('Parsing the data failed because it was encoded incorrectly or an invalid file path.', $code, $previous);
    }
}
