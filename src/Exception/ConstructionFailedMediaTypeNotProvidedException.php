<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class ConstructionFailedMediaTypeNotProvidedException extends InvalidArgumentException
{
    public function __construct(string $filePath, ?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(sprintf('Construction failed because the media type of the file "%s" was not provided.', $filePath), $code, $previous);
    }
}
