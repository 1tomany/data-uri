<?php

namespace OneToMany\DataUri\Exception;

final class WritingTemporaryFileFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct(string $filePath)
    {
        parent::__construct(\sprintf('An error occurred when attempting to write the data to the temporary file "%s". Either the disk is full or the temporary file is no longer available.', $filePath));
    }

}
