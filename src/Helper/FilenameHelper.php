<?php

namespace OneToMany\DataUri\Helper;

use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use Random\RandomError;
use Random\RandomException;
use Random\Randomizer;
use Symfony\Component\Filesystem\Exception\ExceptionInterface as FilesystemExceptionInterface;
use Symfony\Component\Filesystem\Path;

use function assert;
use function max;
use function sprintf;
use function strtolower;
use function trim;

final readonly class FilenameHelper
{
    private function __construct()
    {
    }

    /**
     * @param positive-int $length
     *
     * @return non-empty-string
     */
    public static function generate(int $length): string
    {
        try {
            /** @var non-empty-string $filename */
            $filename = new Randomizer()->getBytesFromString('1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', max(1, $length));
        } catch (RandomException|RandomError $e) {
            throw new RuntimeException('Generating a sufficiently random filename failed.', previous: $e);
        }

        return $filename;
    }

    /**
     * @param non-empty-string $filename
     * @param ?non-empty-string $extension
     *
     * @return non-empty-string
     */
    public static function changeExtension(string $filename, ?string $extension, bool $forceLowercase = true): string
    {
        if (!$filename = trim($filename)) {
            throw new InvalidArgumentException('The filename cannot be empty.');
        }

        $extension = trim($extension ?? '');

        if (!$extension) {
            return $filename;
        }

        try {
            $extension = $forceLowercase ? strtolower($extension) : $extension;

            if (Path::hasExtension($filename, $extension, true)) {
                $filename = Path::changeExtension($filename, $extension);
            } else {
                $filename = sprintf('%s.%s', $filename, $extension);
            }
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Changing the extension of "%s" to "%s" failed.', $filename, $extension), previous: $e);
        }

        assert(!empty($filename));

        return $filename;
    }
}
