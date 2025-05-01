<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Exception\ParsingFailedEmptyDataProvidedException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidBase64EncodedDataException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidDataProvidedException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidFilePathProvidedException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidHashAlgorithmProvidedException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidRfc2397EncodedDataException;
use OneToMany\DataUri\Exception\ProcessingFailedTemporaryFileNotWrittenException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

use function basename;
use function OneToMany\DataUri\parse_data;
use function rawurlencode;
use function sys_get_temp_dir;
use function tempnam;

#[Group('UnitTests')]
final class ParseDataTest extends FileTestCase
{
    public function testParsingDataRequiresValidHashAlgorithm(): void
    {
        $this->expectException(ParsingFailedInvalidHashAlgorithmProvidedException::class);

        parse_data($this->path, null, 'sha-1024');
    }

    public function testParsingDataRequiresNonEmptyData(): void
    {
        $this->expectException(ParsingFailedEmptyDataProvidedException::class);

        parse_data(' ');
    }

    public function testParsingDataRequiresValidRfc2397Format(): void
    {
        $this->expectException(ParsingFailedInvalidRfc2397EncodedDataException::class);

        parse_data('data:image/png,charset=UTF-8,invalid-png-contents');
    }

    public function testParsingDataRequiresValidBase64EncodedData(): void
    {
        $this->expectException(ParsingFailedInvalidBase64EncodedDataException::class);

        parse_data('data:image/png;base64,ümlaut');
    }

    public function testParsingDataRequiresReadableFileToExist(): void
    {
        $this->expectException(ParsingFailedInvalidFilePathProvidedException::class);

        $filesystem = $this->createMock(Filesystem::class);

        $filesystem
            ->expects($this->any())
            ->method('exists')
            ->willReturn(true);

        $filesystem
            ->expects($this->once())
            ->method('readFile')
            ->willThrowException(new IOException('Error'));

        parse_data(data: $this->path, filesystem: $filesystem);
    }

    public function testParsingDataRequiresValidDataUrlSchemeOrValidFilePath(): void
    {
        $this->expectException(ParsingFailedInvalidDataProvidedException::class);

        parse_data('invalid-data-string-and-file-path');
    }

    public function testParsingDataRequiresWritingDataToTemporaryFile(): void
    {
        $this->expectException(ProcessingFailedTemporaryFileNotWrittenException::class);

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->expects($this->once())
            ->method('tempnam')
            ->willThrowException(new IOException('Error'));

        parse_data(data: 'data:text/plain,Test%20data', filesystem: $filesystem);
    }

    public function testParsingDataWithoutBase64EncodedIsDecodedAsAsciiText(): void
    {
        $text = 'Hello, PHP world!';
        $data = rawurlencode($text);

        $file = parse_data('data:text/plain,'.$data);
        $this->assertStringEqualsFile($file->filePath, $text);
    }

    public function testParsingDataCanSetClientName(): void
    {
        $name = 'HelloWorld.txt';
        $file = parse_data(data: 'data:,Hello%20World', clientName: $name);

        $this->assertEquals($name, $file->clientName);
        $this->assertNotEquals($name, $file->fileName);
    }

    public function testParsingDataAsFilePathSetsFileNameAsClientName(): void
    {
        $name = basename($this->path);

        $this->assertNotEmpty($name);
        $this->assertStringEndsWith($name, $this->path);

        $file = parse_data(data: $this->path, clientName: null);

        $this->assertEquals($name, $file->clientName);
        $this->assertNotEquals($name, $file->fileName);
    }

    public function testParsingDataAsFilePathCanHaveClientNameOverwritten(): void
    {
        $name = 'CustomFileName_'.uniqid().'.bin';
        $this->assertStringEndsNotWith($name, $this->path);

        $file = parse_data(data: $this->path, clientName: $name);

        $this->assertEquals($name, $file->clientName);
        $this->assertNotEquals($name, $file->fileName);
    }

    #[DataProvider('providerFilePath')]
    public function testParsingDataAsFilePath(string $filePath): void
    {
        $file = parse_data($filePath);

        $this->assertFileExists($file->filePath);
        $this->assertFileEquals($filePath, $file->filePath);
    }

    /**
     * @return list<list<non-empty-string>>
     */
    public static function providerFilePath(): array
    {
        $prefix = __DIR__.'/data';

        $provider = [
            [$prefix.'/php-logo.png'],
        ];

        return $provider;
    }

    public function testParsingDataCanBeForcedToBeDecodedAsBase64(): void
    {
        // 1x1 Transparent GIF
        $file = parse_data(data: 'R0lGODdhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==', assumeBase64Data: true);

        $this->assertFileExists($file->filePath);
        $this->assertEquals('gif', $file->extension);
    }

    public function testParsingDataAsFilePathCanDeleteOriginalFile(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), '__1n__datauri_test_');

        $this->assertIsString($filePath);
        $this->assertFileExists($filePath);

        $file = parse_data(data: $filePath, deleteOriginalFile: true);

        $this->assertFileExists($file->filePath);
        $this->assertFileDoesNotExist($filePath);
    }
}
