<?php

namespace OneToMany\DataUri\Exception;

final class DecodingDataFailedException extends \InvalidArgumentException implements ExceptionInterface
{
    public function __construct(?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct('Attempting to decode the data provided failed: it was encoded incorrectly or an invalid file path.', $code, $previous);
    }
}
