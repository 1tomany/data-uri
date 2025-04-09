<?php

namespace OneToMany\DataUri\Exception;

final class InvalidBase64EncodedDataUriException extends \InvalidArgumentException implements ExceptionInterface
{
    public function __construct(?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct('Decoding the data failed because it was not correctly encoded as a base64 string.', $code, $previous);
    }
}
