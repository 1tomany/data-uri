<?php

namespace OneToMany\DataUri\Exception;

final class GeneratingByteCountFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct(string $filePath)
    {
        parent::__construct(\sprintf('The byte count for the file "%s" could not be generated.', $filePath));
    }

}
