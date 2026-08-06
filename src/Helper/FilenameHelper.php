<?php

namespace OneToMany\DataUri\Helper;

use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use Random\RandomError;
use Random\RandomException;
use Random\Randomizer;

use function array_filter;
use function array_values;
use function assert;
use function basename;
use function implode;
use function pathinfo;
use function sprintf;
use function str_contains;
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
            return null;
        }

        $filename = trim($filename);

        // Normalize the filename if a complete path was passed
        $filename = basename(str_replace('\\', '/', $filename));

        if ('' === $filename) {
            return null;
        }

        // Split on all characters that we want to remove
        $nameBits = preg_split('/[^A-Za-z0-9.]+/', $filename);

        if (!$nameBits) {
            return null;
        }

        // Remove NULL or empty placeholders
        $bitFilter = function (?string $bit): bool {
            return null !== $bit && '' !== trim($bit);
        };

        $nameBits = array_filter($nameBits, $bitFilter);

        // Compile the filename with hyphens as the spacer
        $filename = implode('-', array_values($nameBits));

        // The normalized filename must be more than just
        // an extension, even if it is technically valid
        $name = pathinfo($filename, PATHINFO_FILENAME);

        if ('' === $name) {
            return null;
        }

        // Normalize hyphens and periods in the name
        if (true === str_contains($filename, '.')) {
            $nameBits = explode('.', trim($filename));

            foreach ($nameBits as $idx => $bit) {
                $nameBits[$idx] = trim($bit, '-');

                if ('' === $nameBits[$idx]) {
                    unset($nameBits[$idx]);
                }
            }

            $filename = trim(implode('.', $nameBits));
        }

        if (strlen($filename) > PHP_MAXPATHLEN) {
            throw new InvalidArgumentException(sprintf('The normalized filename length must be less than or equal to %d characters.', PHP_MAXPATHLEN));
        }

        return '' === $filename ? null : $filename;
    }
}
