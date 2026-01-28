<?php

namespace OneToMany\DataUri\Helper;

use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use Random\RandomError;
use Random\RandomException;
use Random\Randomizer;
use Symfony\Component\Filesystem\Path;

use function implode;
use function strtolower;
use function trim;

final readonly class FilenameGenerator
{
    private function __construct()
    {
    }

    /**
     * @param positive-int $length
     * @param ?non-empty-string $extension
     *
     * @return non-empty-string
     */
    public static function generate(int $length, ?string $extension = null): string
    {
        try {
            /** @var non-empty-string $filename */
            $filename = new Randomizer()->getBytesFromString('1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', \max(1, $length));
        } catch (RandomException|RandomError $e) {
            throw new RuntimeException('Generating a sufficiently random filename failed.', previous: $e);
        }

        // Append the extension if provided
        $extension = trim($extension ?? '');

        if ($extension && !Path::hasExtension($filename, $extension, true)) {
            $filename = implode('.', [$filename, strtolower($extension)]);
        }

        return $filename;
    }
}
