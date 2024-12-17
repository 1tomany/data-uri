<?php

namespace OneToMany\DataUri\Exception;

final class GeneratingHashFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct()
    {
        parent::__construct('A SHA1 hash for the data could not be generated.');
    }

}
