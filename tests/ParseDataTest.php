<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\ParsingFailedEmptyDataProvidedException;
use OneToMany\DataUri\Exception\ParsingFailedFilePathTooLongException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidBase64EncodedDataException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidFilePathProvidedException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidHashAlgorithmProvidedException;
use OneToMany\DataUri\Exception\ParsingFailedInvalidRfc2397EncodedDataException;
use OneToMany\DataUri\Exception\ProcessingFailedTemporaryFileNotWrittenException;
use OneToMany\DataUri\Exception\RuntimeException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function OneToMany\DataUri\parse_data;

// use const PHP_MAXPATHLEN;

#[Group('UnitTests')]
final class ParseDataTest extends TestCase
{
    use TestFileTrait;

    public function testParsingDataRequiresNonEmptyData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data cannot be empty.');

        parse_data(' ');
    }

    public function testParsingDataRequiresDataToNotBeADirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data cannot be a directory.');

        parse_data(__DIR__);
    }

    public function testParsingDataRequiresDataToNotContainNonPrintableBytes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data cannot contain non-printable or NULL bytes.');

        parse_data(\random_bytes(1024));
    }

    public function testParsingDataRequiresDirectoryToBeWritable(): void
    {
        $directory = '/path/to/invalid/directory/';
        $this->assertDirectoryDoesNotExist($directory);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The directory "'.$directory.'" is not writable.');

        parse_data(__DIR__.'/data/pdf-small.pdf', directory: $directory);
    }

    public function testParsingDataDataRequiresReadableFileToExist(): void
    {
        // Arrange: Create Unreadable Virtual File
        $file = vfsStream::newFile('Invoice_1984.pdf');
        $file->chmod(0400)->chown(vfsStream::OWNER_ROOT);

        vfsStream::setup(structure: [$file]);

        // Assert: Virtual File Is Not Readable
        $this->assertFileIsNotReadable($file->url());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The file "'.$file->url().'" is not readable.');

        parse_data(data: $file->url());
    }

    public function testParsingDataRequiresValidDataUri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to decode the data.');

        parse_data('data:image/gif;base64,!R0lG**AQ/ABAIAAAAAAA++ACH5BAEAAAAALAA?AEAOw==');
    }

    public function testParsingDataRequiresWritingDataToTemporaryFile(): void
    {
        // Arrange: Find Writable Directory
        $directory = \sys_get_temp_dir();
        $this->assertDirectoryExists($directory);
        $this->assertDirectoryIsWritable($directory);

        // Assert: Failed to Create Temporary File
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create a file in "'.$directory.'".');

        // Arrange: Mock Filesystem Library
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())->method('tempnam')->willThrowException(new IOException('Error'));

        // Act: Parse Data With Expected Exception
        parse_data(__DIR__.'/data/pdf-small.pdf', directory: $directory, filesystem: $filesystem);
    }

    public function _testParsingEncodedDataCanSetDisplayName(): void
    {
        $name = 'HelloWorld.txt';
        $file = parse_data(data: 'data:,Hello%20world', name: $name);

        $this->assertEquals($name, $file->name);
        $this->assertNotEquals($name, $file->basename);
    }

    #[DataProvider('providerPathAndName')]
    public function _testParsingPathWithoutNameSetsFileName(string $path, string $name): void
    {
        // Arrange: Create Virtual File and Temporary Virtual File
        $vFile = vfsStream::newFile($name)->withContent('Hello, PHP world!');
        $tFile = vfsStream::newFile(Path::getFilenameWithoutExtension($name));

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
        $file = parse_data(data: $path, name: null, filesystem: $filesystem);

        // Assert: Both File Names Are Equal
        $this->assertEquals($name, $file->name);
        $this->assertEquals($name, $file->basename);
    }

    /**
     * @return list<list<non-empty-string>>
     */
    public static function providerPathAndName(): array
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

    public function _testParsingPathCanOverwriteName(): void
    {
        // Arrange: Create File and Name
        $path = $this->createTempFile();
        $name = 'OverwrittenFileName.txt';

        // Assert: Name Does Not Equal File Name
        $this->assertStringEndsNotWith($name, $path);

        // Act: Parse File With Non-Null Name
        $file = parse_data(data: $path, name: $name);

        // Assert: File Name Equals Name
        $this->assertEquals($name, $file->name);
    }

    public function _testParsingFilePathDataCanDeleteOriginalFile(): void
    {
        $data = $this->fetchRandomFile();

        // Assert: Original File Exists
        $this->assertFileExists($data->path);

        // Act: Parse Data and Delete Original File
        $file = parse_data(data: $data->path, delete: true);

        $this->assertFileExists($file->path);
        $this->assertFileDoesNotExist($data->path);
    }

    #[DataProvider('providerEncodedDataAndMetadata')]
    public function _testParsingEncodedData(
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
    public function _testParsingFilePathData(
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
