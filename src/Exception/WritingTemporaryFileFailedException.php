<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class WritingTemporaryFileFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct(string $path)
    {
        parent::__construct(sprintf('An error occurred when attempting to write the data to temporary file "%s". Either the disk is full or the temporary file is no longer available.', $path));
    }

}
