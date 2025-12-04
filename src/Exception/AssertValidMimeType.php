<?php

namespace OneToMany\DataUri\Exception;

use OneToMany\DataUri\Contract\Record\SmartFileInterface;

final readonly class AssertValidMimeType
{
    private function __construct()
    {
    }

    /**
     * @return non-empty-string
     */
    public static function assert(?string $mimeType): string
    {
        $mimeType = \trim($mimeType ?? '');

        if (!\preg_match(SmartFileInterface::MIME_TYPE_REGEX, $mimeType)) {
            throw new InvalidArgumentException(\sprintf('The MIME type "%s" is invalid.', $mimeType));
        }

        return \strtolower($mimeType); // @phpstan-ignore-line
    }
}
