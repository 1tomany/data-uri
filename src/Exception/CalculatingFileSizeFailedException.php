<?php

namespace OneToMany\DataUri\Exception;

final class CalculatingFileSizeFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct(string $filePath)
    {
        parent::__construct(\sprintf('The size for the file "%s" could not be calculated.', $filePath));
    }

}
