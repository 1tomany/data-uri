<?php

namespace OneToMany\DataUri\Exception;

final class WritingTemporaryFileFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct(string $pathname)
    {
        parent::__construct(sprintf('Failed to write the decoded data to the temporary file. Either the disk is full or the file "%s" is not writable.', $pathname));
    }

}
