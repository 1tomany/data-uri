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
     * @return non-empty-string
     */
    public static function assert(?string $mimeType): string
    {
        $mimeType = trim($mimeType ?? '');

        if (!$mimeType) {
            throw new InvalidArgumentException('The MIME type cannot be empty.');
        }

        if (!preg_match('/^\w+\/[-+.\w]+$/i', $mimeType)) {
            throw new InvalidArgumentException(sprintf('The MIME type "%s" is invalid.', $mimeType));
        }

        return strtolower($mimeType);
    }
}
