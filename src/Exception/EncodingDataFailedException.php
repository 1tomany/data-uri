<?php

namespace OneToMany\DataUri\Exception;

final class EncodingDataFailedException extends \InvalidArgumentException implements ExceptionInterface
{

    public function __construct(string $filePath, ?\Throwable $previous = null)
    {
        parent::__construct(message: \sprintf('Encoding the file "%s" failed because the file does not exist.', $filePath), previous: $previous);
    }

}
