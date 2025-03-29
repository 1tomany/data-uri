<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class CreatingTemporaryFileFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct(string $tempDir)
    {
        parent::__construct(sprintf('An error occurred when attempting to create a temporary file. Either the disk is full or the directory "%s" is not writable.', $tempDir));
    }

}
