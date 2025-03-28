<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class GeneratingSizeFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct(string $path)
    {
        parent::__construct(sprintf('The filesize for the file "%s" could not be generated.', $path));
    }

}
