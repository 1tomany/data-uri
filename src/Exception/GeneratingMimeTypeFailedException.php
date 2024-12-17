<?php

namespace OneToMany\DataUri\Exception;

final class GeneratingMimeTypeFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct()
    {
        parent::__construct('A MIME type for the data could not be generated.');
    }

}
