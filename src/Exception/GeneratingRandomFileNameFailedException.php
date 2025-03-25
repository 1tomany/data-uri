<?php

namespace OneToMany\DataUri\Exception;

final class GeneratingRandomFileNameFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct()
    {
        parent::__construct('The random number generator failed to generate a sufficiently long random file name.');
    }

}
