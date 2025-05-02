<?php

namespace OneToMany\DataUri\Tests;

use PHPUnit\Framework\TestCase;

use function array_rand;
use function file_get_contents;

/**
 * @phpstan-type TestFile array{filePath: non-empty-string, fileName: non-empty-string, mediaType: non-empty-string, extension: non-empty-string}
 */
abstract class FileTestCase extends TestCase
{
    /** @var non-empty-string */
    protected static string $dataDirectory;

    /** @var non-empty-string */
    protected string $filePath;

    /** @var non-empty-string */
    protected string $fileName;

    /** @var non-empty-string */
    protected string $mediaType;

    /** @var ?list<TestFile> */
    protected static ?array $testFiles = null;

    public static function setUpBeforeClass(): void
    {
        self::$dataDirectory = __DIR__.'/data';

        // @phpstan-ignore-next-line
        self::$testFiles ??= require_once __DIR__.'/fixtures.php';
    }

    protected function setUp(): void
    {
        // @phpstan-ignore-next-line
        $file = self::$testFiles[array_rand(self::$testFiles)];

        $this->filePath = $file['filePath'];
        $this->fileName = $file['fileName'];
        $this->mediaType = $file['mediaType'];
    }

    protected function getFilePath(string $fileName): string
    {
        return self::$dataDirectory.'/'.$fileName;
    }

    protected function readFile(string $fileName): string
    {
        return @file_get_contents($this->getFilePath($fileName)) ?: throw new \RuntimeException(sprintf('Failed to read "%s".', $fileName));
    }
}
