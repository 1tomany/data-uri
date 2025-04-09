<?php

namespace OneToMany\DataUri\Exception;

final class ParsingFailedEmptyDataProvidedException extends \InvalidArgumentException implements ExceptionInterface
{
    public function __construct(?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct('Parsing the data failed because an empty value was provided.', $code, $previous);
    }
}
