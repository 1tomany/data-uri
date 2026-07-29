<?php

namespace OneToMany\DataUri\Exception;

use OneToMany\DataUri\Validator\MimeTypeValidator;

/**
 * @deprecated use OneToMany\DataUri\Validator\MimeTypeValidator
 */
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
        return MimeTypeValidator::validate($format);
    }
}
