<?php

namespace OneToMany\DataUri\Exception;

final class GeneratingDataUriFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct(string $filepath)
    {
        parent::__construct(sprintf('A data URI representation of the file could not be generated because the file "%s" does not exist or is not readable.', $filepath));
    }

}
