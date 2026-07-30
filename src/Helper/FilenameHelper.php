<?php

namespace OneToMany\DataUri\Helper;

use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use Random\RandomError;
use Random\RandomException;
use Random\Randomizer;

use function assert;
use function basename;
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
    public static function sanitize(?string $filename): ?string
    {
        if (null === $filename) {
            return $filename;
        }

        // Basic cleanup to ensure we're working with a single file
        $filename = basename(str_replace('\\', '/', trim($filename)));

        if ('' === $filename) {
            return null;
        }

        if (null === $sanitized = preg_replace('/[^\pL\pN._-]+/u', '_', $filename)) {
            $sanitized = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename);
        }

        if (null === $sanitized) {
            return $sanitized;
        }

        $sanitized = trim($sanitized, '.');

        if ('' === $sanitized) {
            return null;
        }

        if (strlen($sanitized) > self::MAXIMUM_FILENAME_LENGTH) {
            $sanitized = substr($sanitized, -self::MAXIMUM_FILENAME_LENGTH);
        }

        return $sanitized;
    }
}
