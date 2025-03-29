<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class RenamingTemporaryFileFailedException extends \RuntimeException implements ExceptionInterface
{

    public function __construct(string $fromPath, string $toPath)
    {
        parent::__construct(sprintf('An error occurred when attempting to rename the temporary file "%s" to "%s".', $fromPath, $toPath));
    }

}
