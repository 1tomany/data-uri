<?php

namespace OneToMany\DataUri\Exception;

final class GeneratingExtensionFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct(string $filePath)
    {
        parent::__construct(\sprintf('A file extension for the file "%s" could not be generated.', $filePath));
    }

}
