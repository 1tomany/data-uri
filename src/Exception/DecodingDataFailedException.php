<?php

namespace OneToMany\DataUri\Exception;

final class DecodingDataFailedException extends \InvalidArgumentException implements ExceptionInterface
{

    public function __construct()
    {
        parent::__construct('Parsing the data provided failed: either the value was not a valid file path or not encoded correctly.');
    }

}
