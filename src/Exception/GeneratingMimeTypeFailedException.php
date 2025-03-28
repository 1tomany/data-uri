<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class GeneratingMimeTypeFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct(string $path)
    {
        parent::__construct(sprintf('The MIME type for the file "%s" could not be generated.', $path));
    }

}
