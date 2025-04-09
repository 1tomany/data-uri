<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class ParsingFailedInvalidHashAlgorithmProvidedException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $hashAlgorithm)
    {
        parent::__construct(sprintf('Parsing the data failed because the hash algorithm "%s" is not valid.', $hashAlgorithm));
    }
}
