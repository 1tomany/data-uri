<?php

namespace OneToMany\DataUri\Exception;

final class GeneratingHashFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct(string $filePath)
    {
        parent::__construct(\sprintf('A SHA1 hash for the file "%s" could not be generated.', $filePath));
    }

}
