<?php

namespace OneToMany\DataUri\Helper;

use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use Random\RandomError;
use Random\RandomException;
use Random\Randomizer;
use Symfony\Component\Filesystem\Exception\ExceptionInterface as FilesystemExceptionInterface;
use Symfony\Component\Filesystem\Path;

use function basename;
use function OneToMany\IsEmpty\is_empty;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_replace;
use function strlen;
use function strtolower;
use function trim;

final readonly class FilenameHelper
{
    private const int MAXIMUM_FILENAME_LENGTH = 128;

    private function __construct()
    {
    }

    /**
     * @return non-empty-string
     */
    public static function generate(int $length): string
    {
        if ($length < 1) {
            throw new InvalidArgumentException('The filename length must be greater than zero.');
        }

        try {
            $filename = new Randomizer()->getBytesFromString('1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', $length);
        } catch (RandomException|RandomError $e) {
            throw new RuntimeException('Generating a sufficiently random filename failed.', previous: $e);
        }

        if ('' === $filename) {
            throw new RuntimeException('Generating a non-empty random filename failed.');
        }

        return $filename;
    }

    /**
     * Converts an untrusted display name into a safe single path segment.
     *
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
            throw new InvalidArgumentException(sprintf('The sanitized filename "%s" is longer than the maximum length of %d %s.', $sanitized, self::MAXIMUM_FILENAME_LENGTH, 1 === self::MAXIMUM_FILENAME_LENGTH ? 'character' : 'characters'));
        }

        return '' !== $filename ? $filename : null;
    }

    /**
     * @param non-empty-string $filename
     * @param ?non-empty-string $extension
     *
     * @return non-empty-string
     */
    public static function changeExtension(string $filename, ?string $extension, bool $lowercase = true): string
    {
        if (is_empty($filename = trim($filename), false)) {
            throw new InvalidArgumentException('The filename cannot be empty.');
        }

        if (is_empty($extension)) {
            return $filename;
        }

        $extension = trim($extension);

        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9.]*$/', $extension)) {
            throw new InvalidArgumentException(sprintf('The extension "%s" is invalid.', $extension));
        }

        try {
            // Lowercase the extension if requested by the caller
            $extension = $lowercase ? strtolower($extension) : $extension;

            if (Path::hasExtension($filename, $extension, true)) {
                $filename = Path::changeExtension($filename, $extension);
            } else {
                $filename = sprintf('%s.%s', $filename, $extension);
            }
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Changing the extension of the file "%s" to "%s" failed.', $filename, $extension), previous: $e);
        }

        if (is_empty($filename)) {
            throw new RuntimeException('Changing the file extension produced an empty filename.');
        }

        return $filename;
    }
}
