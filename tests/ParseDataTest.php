<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Exception\ParsingFailedEmptyDataProvidedException;
use OneToMany\DataUri\Exception\ParsingFailedFilePathTooLongException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidBase64EncodedDataException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidFilePathProvidedException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidHashAlgorithmProvidedException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidRfc2397EncodedDataException;
use OneToMany\DataUri\Exception\ProcessingFailedTemporaryFileNotWrittenException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function OneToMany\DataUri\parse_data;
use function uniqid;
use function vsprintf;

use const PHP_MAXPATHLEN;

#[Group('UnitTests')]
final class ParseDataTest extends TestCase
{
    use TestFileTrait;

    public function testParsingDataRequiresValidHashAlgorithm(): void
    {
        $this->expectException(ParsingFailedInvalidHashAlgorithmProvidedException::class);

        parse_data('data:text/plain,Hello%20world', null, 'sha-1024');
    }

    public function testParsingDataRequiresNonEmptyData(): void
    {
        $this->expectException(ParsingFailedEmptyDataProvidedException::class);

        parse_data(' ');
    }

    public function testParsingEncodedDataCanBeForcedToBeDecodedAsBase64(): void
    {
        // 1x1 Transparent GIF
        $data = $this->readFileContents(...[
            'fileName' => 'gif-base64.txt',
        ]);

        // Assert: Not a Data URI
        $this->assertStringStartsNotWith('data:', $data);

        // Act: Parse Data With Base64 Assumption
        $file = parse_data(data: $data, assumeBase64Data: true);

        // Assert: Data Is Successfully Parsed
        $this->assertEquals('image/gif', $file->type);
        $this->assertStringEndsWith('.gif', $file->name);
    }

    public function testParsingEncodedDataRequiresValidRfc2397Format(): void
    {
        $this->expectException(ParsingFailedInvalidRfc2397EncodedDataException::class);

        parse_data('data:image/png,charset=UTF-8,invalid-png-contents');
    }

    public function testParsingEncodedDataRequiresValidBase64EncodedData(): void
    {
        $this->expectException(ParsingFailedInvalidBase64EncodedDataException::class);

        parse_data('data:image/png;base64,ümlaut');
    }

    public function testParsingEncodedDataWithoutBase64OptionIsDecodedAsAsciiText(): void
    {
        $text = 'Hello, PHP world!';
        $data = 'Hello%2C%20PHP%20world%21';

        $file = parse_data('data:text/plain,'.$data);
        $this->assertStringEqualsFile($file->path, $text);
    }

    public function testParsingFilePathRequiresFilePathLengthToBeLessThanOrEqualToTheMaximumPathLength(): void
    {
        $this->expectException(ParsingFailedFilePathTooLongException::class);

        parse_data(str_repeat('a', PHP_MAXPATHLEN + 1));
    }

    public function testParsingFilePathDataRequiresReadableFileToExist(): void
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

        parse_data(data: __FILE__, filesystem: $filesystem);
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

    public function testParsingEncodedDataWithoutContentTypeDefaultsToTextPlain(): void
    {
        $file = parse_data('data:,Hello%20world');

        $this->assertEquals('text/plain', $file->type);
        $this->assertStringEndsWith('.txt', $file->name);
    }

    public function testParsingEncodedDataCanSetDisplayName(): void
    {
        $name = 'HelloWorld.txt';
        $file = parse_data(data: 'data:,Hello%20world', displayName: $name);

        $this->assertEquals($name, $file->displayName);
        $this->assertNotEquals($name, $file->name);
    }

    #[DataProvider('providerFilePathAndDisplayName')]
    public function testParsingFilePathDataSetsFileNameAsDisplayName(
        string $filePath,
        string $displayName,
    ): void {
        // Arrange: Create Virtual File and Temporary Virtual File
        $vFile = vfsStream::newFile($displayName)->withContent('Hello, PHP world!');
        $tFile = vfsStream::newFile(Path::getFilenameWithoutExtension($displayName));

        // Arrange: Create Virtual File System
        vfsStream::setup(structure: [$vFile, $tFile]);

        // Arrange: Mock Symfony Filesystem Component
        $filesystem = $this->createMock(Filesystem::class);

        $filesystem
            ->expects($this->once())
            ->method('readFile')
            ->willReturn($vFile->getContent());

        $filesystem
            ->expects($this->once())
            ->method('tempnam')
            ->willReturn($tFile->url());

        // Act: Parse File With Null Display Name
        $file = parse_data(data: $filePath, displayName: null, filesystem: $filesystem);

        // Assert: Display Name Equals Original File Name
        $this->assertEquals($displayName, $file->displayName);
        $this->assertEquals($file->name, $file->displayName);
    }

    /**
     * @return list<list<non-empty-string>>
     */
    public static function providerFilePathAndDisplayName(): array
    {
        $provider = [
            ['test.jpeg', 'test.jpeg'],
            ['/test.txt', 'test.txt'],
            ['./test.pdf', 'test.pdf'],
            ['/tmp/test.png', 'test.png'],
            ['http://1tomany-cdn.com/test.gif', 'test.gif'],
            ['https://1tomany-cdn.com/test.pdf', 'test.pdf'],
            ['https://1tomany-cdn.com/test.pdf?id=10', 'test.pdf'],
            ['https://1tomany-cdn.com/test.pdf?id=10&name=Vic', 'test.pdf'],
            ['https://1tomany-cdn.com/b1/b2/test.jpg', 'test.jpg'],
        ];

        return $provider;
    }

    public function testParsingFilePathDataCanHaveDisplayNameOverwritten(): void
    {
        $data = $this->fetchRandomFile();

        // Arrange: Create Unique Display Name
        $displayName = vsprintf('test-%s.%s', [
            uniqid('', true), $data->extension,
        ]);

        // Assert: Display Name Is Unique
        $this->assertNotEquals($displayName, $data->displayName);

        // Act: Parse File With Unique Display Name
        $file = parse_data(data: $data->path, displayName: $displayName);

        // Assert: Display Name Equals Unique Display Name
        $this->assertEquals($displayName, $file->displayName);
    }

    public function testParsingFilePathDataCanDeleteOriginalFile(): void
    {
        $data = $this->fetchRandomFile();

        // Assert: Original File Exists
        $this->assertFileExists($data->path);

        // Act: Parse Data and Delete Original File
        $file = parse_data(data: $data->path, deleteOriginalFile: true);

        $this->assertFileExists($file->path);
        $this->assertFileDoesNotExist($data->path);
    }

    #[DataProvider('providerEncodedDataAndMetadata')]
    public function testParsingEncodedData(
        string $data,
        string $contentType,
        int $byteCount,
        string $extension,
    ): void {
        $file = parse_data($data);

        $this->assertFileExists($file->path);
        $this->assertEquals($contentType, $file->type);
        $this->assertEquals($byteCount, $file->size);
        $this->assertEquals($extension, $file->extension);
    }

    /**
     * @return list<list<int|non-empty-string>>
     */
    public static function providerEncodedDataAndMetadata(): array
    {
        $provider = [
            ['data:,Test', 'text/plain', 4, 'txt'],
            ['data:text/plain,Test', 'text/plain', 4, 'txt'],
            ['data:text/plain,Test', 'text/plain', 4, 'txt'],
            ['data:text/plain;charset=US-ASCII,Hello%20world', 'text/plain', 11, 'txt'],
            ['data:;base64,SGVsbG8sIHdvcmxkIQ==', 'text/plain', 13, 'txt'],
            ['data:text/plain;base64,SGVsbG8sIHdvcmxkIQ==', 'text/plain', 13, 'txt'],
            ['data:application/json,%7B%22id%22%3A10%7D', 'application/json', 9, 'bin'],
            ['data:application/json;base64,eyJpZCI6MTB9', 'application/json', 9, 'bin'],

            // @see https://stackoverflow.com/questions/17279712/what-is-the-smallest-possible-valid-pdf#comment59467299_17280876
            ['data:application/pdf;base64,JVBERi0xLg10cmFpbGVyPDwvUm9vdDw8L1BhZ2VzPDwvS2lkc1s8PC9NZWRpYUJveFswIDAgMyAzXT4+XT4+Pj4+Pg==', 'application/pdf', 67, 'pdf'],
        ];

        return $provider;
    }

    #[DataProvider('providerFilePathAndMetadata')]
    public function testParsingFilePathData(
        string $filePath,
        string $contentType,
        int $byteCount,
        string $extension,
    ): void {
        $file = parse_data($filePath);

        $this->assertFileExists($file->path);
        $this->assertEquals($contentType, $file->type);
        $this->assertEquals($byteCount, $file->size);
        $this->assertEquals($extension, $file->extension);
    }

    /**
     * @return list<list<int|non-empty-string>>
     */
    public static function providerFilePathAndMetadata(): array
    {
        $dir = __DIR__.'/data';

        return [
            [$dir.'/pdf-small.pdf', 'application/pdf', 36916, 'pdf'],
            [$dir.'/png-small.png', 'image/png', 10289, 'png'],
            [$dir.'/text-small.txt', 'text/plain', 86, 'txt'],
            [$dir.'/word-small.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 6657, 'docx'],
        ];
    }
}
