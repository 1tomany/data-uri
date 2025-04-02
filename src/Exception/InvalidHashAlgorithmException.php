<?php

namespace OneToMany\DataUri\Exception;

final class InvalidHashAlgorithmException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $hashAlgorithm)
    {
        parent::__construct(\sprintf('The hash algorithm "%s" is not valid and can not be used.', $hashAlgorithm));
    }
}
