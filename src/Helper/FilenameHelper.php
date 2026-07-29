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
use function preg_match;
use function preg_replace;
use function preg_split;
use function sprintf;
use function str_replace;
use function strlen;
use function strtolower;
use function trim;

use const PREG_SPLIT_NO_EMPTY;

final readonly class FilenameHelper
{
    private const int MAXIMUM_FILENAME_LENGTH = 180;

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
        $filename = trim((string) $filename);

        if ('' === $filename) {
            return null;
        }

        $filename = basename(str_replace('\\', '/', $filename));
        $originalFilename = $filename;
        $filename = preg_replace('/[^\pL\pN._-]+/u', '_', $filename);

        if (null === $filename) {
            $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $originalFilename);
        }

        $filename = trim((string) $filename, ". \t\n\r\0\x0B");

        if ('' === $filename || '.' === $filename || '..' === $filename) {
            return null;
        }

        if (strlen($filename) > self::MAXIMUM_FILENAME_LENGTH) {
            $characters = preg_split('//u', $filename, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $filename = '';

            foreach ($characters as $character) {
                if (strlen($filename.$character) > self::MAXIMUM_FILENAME_LENGTH) {
                    break;
                }

                $filename .= $character;
            }
        }

        return '' !== $filename ? $filename : null;
    }

    /**
     * @param non-empty-string $filename
     * @param ?non-empty-string $extension
     *
     * @return non-empty-string
     */
    public static function changeExtension(string $filename, ?string $extension, bool $forceLowercase = true): string
    {
        $filename = trim($filename);

        if ('' === $filename) {
            throw new InvalidArgumentException('The filename cannot be empty.');
        }

        $extension = trim((string) $extension);

        if ('' === $extension) {
            return $filename;
        }

        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $extension)) {
            throw new InvalidArgumentException(sprintf('The extension "%s" is invalid.', $extension));
        }

        try {
            $extension = $forceLowercase ? strtolower($extension) : $extension;

            if (Path::hasExtension($filename, $extension, true)) {
                $filename = Path::changeExtension($filename, $extension);
            } else {
                $filename = sprintf('%s.%s', $filename, $extension);
            }
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Changing the extension of the file "%s" to "%s" failed.', $filename, $extension), previous: $e);
        }

        if ('' === $filename) {
            throw new RuntimeException('Changing the file extension produced an empty filename.');
        }

        return $filename;
    }
}
