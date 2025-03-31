<?php

namespace OneToMany\DataUri\Exception;

final class DecodingDataFailedException extends \InvalidArgumentException implements ExceptionInterface
{

    public function __construct(?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct('Parsing the data provided failed: either the value was not a valid file path or it was not encoded correctly.', $code, $previous);
    }

}
