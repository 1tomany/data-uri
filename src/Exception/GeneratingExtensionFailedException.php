<?php

namespace OneToMany\DataUri\Exception;

final class GeneratingExtensionFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct()
    {
        parent::__construct('A file extension for the data could not be generated.');
    }

}
