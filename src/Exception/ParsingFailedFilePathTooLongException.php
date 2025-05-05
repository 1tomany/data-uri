<?php

namespace OneToMany\DataUri\Exception;

use function sprintf;

final class ParsingFailedFilePathTooLongException extends \InvalidArgumentException implements ExceptionInterface
{
    public function __construct(?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(sprintf('Parsing the data failed because the length of the file path exceeds the maximum limit of "%d" characters.', \PHP_MAXPATHLEN), $code, $previous);
    }
}
