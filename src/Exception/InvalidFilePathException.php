<?php

namespace OneToMany\DataUri\Exception;

final class InvalidFilePathException extends \InvalidArgumentException implements ExceptionInterface
{
    public function __construct(?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct('Decoding the data failed because the file provided could not be found.', $code, $previous);
    }
}
