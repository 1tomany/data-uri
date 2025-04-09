<?php

namespace OneToMany\DataUri\Exception;

final class EmptyDataProvidedException extends \InvalidArgumentException implements ExceptionInterface
{
    public function __construct(?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct('Decoding the data failed because an empty string or null value was provided.', $code, $previous);
    }
}
