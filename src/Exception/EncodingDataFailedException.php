<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class EncodingDataFailedException extends \InvalidArgumentException implements ExceptionInterface
{

    public function __construct(string $filePath)
    {
        parent::__construct(sprintf('Encoding the file "%s" failed because the file does not exist.', $filePath));
    }

}
