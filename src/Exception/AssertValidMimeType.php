<?php

namespace OneToMany\DataUri\Exception;

use function preg_match;
use function sprintf;
use function strtolower;
use function trim;

final readonly class AssertValidMimeType
{
    private function __construct()
    {
    }

    /**
     * Attempts to validate that a string matches the basic MIME type structure.
     *
     * @return non-empty-lowercase-string
     *
     * @throws InvalidArgumentException the $format is an empty string
     * @throws InvalidArgumentException the $format does not match the MIME type structure
     */
    public static function assert(?string $format): string
    {
        if (!$format = trim($format ?? '')) {
            throw new InvalidArgumentException('The format cannot be empty.');
        }

        if (!preg_match('/^\w+\/[-+.\w]+$/i', $format)) {
            throw new InvalidArgumentException(sprintf('The format "%s" is invalid.', $format));
        }

        return strtolower($format);
    }
}
