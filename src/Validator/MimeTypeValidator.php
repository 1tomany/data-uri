<?php

namespace OneToMany\DataUri\Validator;

use OneToMany\DataUri\Exception\InvalidArgumentException;

use function preg_match;
use function sprintf;
use function strtolower;
use function trim;

final readonly class MimeTypeValidator
{
    private function __construct()
    {
    }

    /**
     * @return non-empty-lowercase-string
     */
    public static function validate(?string $format): string
    {
        $format = trim((string) $format);

        if ('' === $format) {
            throw new InvalidArgumentException('The format cannot be empty.');
        }

        if (!preg_match("/^[A-Za-z0-9!#\\$%&'*+.^_`|~-]+\\/[A-Za-z0-9!#\\$%&'*+.^_`|~-]+$/", $format)) {
            throw new InvalidArgumentException(sprintf('The format "%s" is invalid.', $format));
        }

        return strtolower($format);
    }
}
