<?php

namespace OneToMany\DataUri\Helper;

use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use Random\RandomError;
use Random\RandomException;
use Random\Randomizer;
use Symfony\Component\Filesystem\Path;

use function array_filter;
use function array_map;
use function assert;
use function basename;
use function explode;
use function implode;
use function pathinfo;
use function preg_replace;
use function sprintf;
use function str_replace;
use function strlen;
use function trim;

use const PATHINFO_FILENAME;
use const PHP_MAXPATHLEN;

final readonly class FilenameHelper
{
    /**
     * Maximum length a randomly generated name can be.
     *
     * @var positive-int
     */
    public const int MAXIMUM_GENERATED_LENGTH = 128;

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

        if ($length > self::MAXIMUM_GENERATED_LENGTH) {
            throw new InvalidArgumentException(sprintf('The length must be less than or equal to %d.', self::MAXIMUM_GENERATED_LENGTH));
        }

        try {
            $filename = new Randomizer()->getBytesFromString('1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', $length);
        } catch (RandomException|RandomError $e) {
            throw new RuntimeException('Generating a sufficiently random filename failed.', previous: $e);
        }

        assert('' !== $filename, 'An empty filename was generated.');

        return $filename;
    }

    /**
     * @return ?non-empty-string
     */
    public static function normalize(?string $filename): ?string
    {
        if (null === $filename) {
            return $filename;
        }

        // Normalize the filename if a complete path was passed
        $filename = basename(str_replace('\\', '/', trim($filename)));

        if ('' === $filename) {
            return null;
        }

        $filename = str_replace(['-', '_'], ' ', $filename);

        // Remove non-alphanumeric and non-period characters
        $mapper = static function (string $nameBit): ?string {
            return preg_replace('/[^A-Za-z0-9.]+/', '', $nameBit);
        };

        $nameBits = array_map($mapper, explode(' ', $filename));

        // Remove NULL or empty string placeholders
        $filter = static function (?string $v): bool {
            return null !== $v && '' !== trim($v);
        };

        $nameBits = array_filter($nameBits, $filter);

        // Rebuild the filename with hyphens
        $filename = implode('-', $nameBits);

        // $mapper = static function (string $nameBit): string {
        //     return trim(trim($nameBit), '-');
        // };

        // $nameBits = array_map($mapper, explode('.', $filename));

        // $nameBits = array_filter($nameBits, $filter);
        // $filename = implode('.', $nameBits);

        // The normalized filename must be more than just an
        // extension, even if it is technically a valid name
        if ('' !== pathinfo($filename, PATHINFO_FILENAME)) {
            $filename = str_replace('-.', '.', $filename);

            if (strlen($filename) > PHP_MAXPATHLEN) {
                throw new InvalidArgumentException(sprintf('The normalized filename length must be less than or equal to %d characters.', PHP_MAXPATHLEN));
            }

            return $filename;
        }

        return null;
    }
}
