<?php

namespace OneToMany\DataUri\Exception;

final class WritingTemporaryFileFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct()
    {
        parent::__construct('Failed to write the decoded data to the temporary file. Either the disk is full or the temporary file is no longer available.');
    }

}
