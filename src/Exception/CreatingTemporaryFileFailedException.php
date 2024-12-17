<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class CreatingTemporaryFileFailedException extends \InvalidArgumentException implements ExceptionInterface
{

    public function __construct(string $tmpdir)
    {
        parent::__construct(sprintf('Failed to create a file to temporarily store the decoded data. Either the disk is full or the directory "%s" is not writable.', $tmpdir));
    }

}
