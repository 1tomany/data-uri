<?php

namespace OneToMany\DataUri\Helper;

use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use Random\RandomError;
use Random\RandomException;
use Random\Randomizer;
use Symfony\Component\String\Slugger\AsciiSlugger;

use function array_filter;
use function array_values;
use function assert;
use function basename;
use function implode;
use function pathinfo;
use function sprintf;
use function str_replace;
use function strlen;
use function Symfony\Component\String\u;
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

        $slugger = new AsciiSlugger('en');

        $nameBits = u($filename)->split('.');

        foreach ($nameBits as $idx => $nameBit) {
            $nameBits[$idx] = $slugger->slug($nameBit->trim()->trim('-_')->toString());
        }

        return u('.')->join($nameBits)->toString();
    }
}
