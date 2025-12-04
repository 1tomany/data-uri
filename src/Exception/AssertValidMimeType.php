<?php

namespace OneToMany\DataUri\Exception;

use OneToMany\DataUri\Contract\Record\SmartFileInterface;

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
     * @return non-empty-string
     */
    public static function assert(?string $mimeType): string
    {
        $mimeType = trim($mimeType ?? '');

        if (!$mimeType) {
            throw new InvalidArgumentException('The MIME type cannot be empty.');
        }

        if (!preg_match(SmartFileInterface::MIME_TYPE_REGEX, $mimeType)) {
            throw new InvalidArgumentException(sprintf('The MIME type "%s" is invalid.', $mimeType));
        }

        return strtolower($mimeType);
    }
}
