<?php

namespace OneToMany\DataUri\Exception;

final class ParsingFailedInvalidRfc2397EncodedDataException extends \InvalidArgumentException implements ExceptionInterface
{
    public function __construct(?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct('Parsing the data failed because it did not follow the "data" URL scheme outlined in RFC2397. See "https://www.rfc-editor.org/rfc/rfc2397.html" for more details.', $code, $previous);
    }
}
