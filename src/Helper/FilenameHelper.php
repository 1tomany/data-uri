<?php

namespace OneToMany\DataUri\Helper;

use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use Random\RandomError;
use Random\RandomException;
use Random\Randomizer;

use function array_filter;
use function array_map;
use function assert;
use function basename;
use function explode;
use function implode;
use function preg_replace;
use function sprintf;
use function str_replace;
use function strlen;
use function substr;
use function trim;

final readonly class FilenameHelper
{
    /**
     * Maximum length a filename (including extension) can be.
     *
     * @var positive-int
     */
    private const int MAXIMUM_FILENAME_LENGTH = 128;

    private function __construct()
    {
    }

    /**
     * @return non-empty-string
     *
     * @throws InvalidArgumentException when the length is not positive
     * @throws InvalidArgumentException when the length is too long
     * @throws RuntimeException when generating a sufficiently random name fails
     */
    public static function generate(int $length): string
    {
        if ($length < 1) {
            throw new InvalidArgumentException('The length must be positive.');
        }

        if ($length > self::MAXIMUM_FILENAME_LENGTH) {
            throw new InvalidArgumentException(sprintf('The length must be less than or equal to %d.', self::MAXIMUM_FILENAME_LENGTH));
        }

        try {
            $fileName = new Randomizer()->getBytesFromString('1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', $length);
        } catch (RandomException|RandomError $e) {
            throw new RuntimeException('Generating a sufficiently random filename failed.', previous: $e);
        }

        assert('' !== $fileName, 'An empty filename was generated.');

        return $fileName;
    }

    /**
     * @return ?non-empty-string
     */
    public static function normalize(?string $fileName): ?string
    {
        if (null === $fileName) {
            return $fileName;
        }

        // Normalize the filename if a complete path was passed
        $fileName = basename(str_replace('\\', '/', trim($fileName)));

        if ('' === $fileName) {
            return null;
        }

        // Remove all non-alphanumeric and non-period characters
        $mapper = static function (string $nameBit): ?string {
            return preg_replace('/[^A-Za-z0-9.]+/', '', $nameBit);
        };

        $nameBits = array_map($mapper, explode(' ', $fileName));

        // Remove empty or NULL placeholders
        $nameBits = array_filter($nameBits, static function (?string $v): bool {
            return null !== $v && '' !== trim($v);
        });

        $fileName = trim(implode('-', $nameBits));

        if ('' === $fileName) {
            return null;
        }

        if (strlen($fileName) > self::MAXIMUM_FILENAME_LENGTH) {
            $fileName = substr($fileName, -self::MAXIMUM_FILENAME_LENGTH);
        }

        return $fileName;
    }
}
