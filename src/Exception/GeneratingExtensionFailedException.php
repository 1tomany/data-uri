<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class GeneratingExtensionFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct(string $path)
    {
        parent::__construct(sprintf('A file extension for the file "%s" could not be generated.', $path));
    }

}
