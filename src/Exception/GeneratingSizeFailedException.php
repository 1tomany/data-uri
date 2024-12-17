<?php

namespace OneToMany\DataUri\Exception;

final class GeneratingSizeFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct()
    {
        parent::__construct('The filesize for the data could not be generated.');
    }

}
