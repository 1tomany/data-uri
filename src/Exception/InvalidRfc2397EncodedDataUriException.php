<?php

namespace OneToMany\DataUri\Exception;

final class InvalidRfc2397EncodedDataUriException extends \InvalidArgumentException implements ExceptionInterface
{
    public function __construct(?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct('Decoding the data failed because it did not follow the "data" URL scheme outlined in RFC2397.', $code, $previous);
    }
}
