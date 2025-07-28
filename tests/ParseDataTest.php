<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

use function assert;
use function base64_encode;
use function basename;
use function OneToMany\DataUri\parse_base64_data;
use function OneToMany\DataUri\parse_data;
use function OneToMany\DataUri\parse_text_data;
use function random_bytes;
use function sys_get_temp_dir;
use function vsprintf;

#[Group('UnitTests')]
final class ParseDataTest extends TestCase
{
    use TestFileTrait;

    public function testParsingDataRequiresStringableData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data must be a non-NULL string or implement the "\Stringable" interface.');

        parse_data(null);
    }

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

        parse_data(random_bytes(1024));
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

        parse_data($file->url());
    }

    public function testParsingDataRequiresValidDataUri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to decode the data.');

        parse_data('data:image/gif;base64,!R0lG**AQ/ABAIAAAAAAA++ACH5BAEAAAAALAA?AEAOw==');
    }

    public function testParsingDataRequiresWritingDataToTemporaryFile(): void
    {
        // Arrange: Writable Directory
        $directory = sys_get_temp_dir();

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

    public function testParsingDataCanSetName(): void
    {
        // Arrange: Name and Data
        $name = 'Hello_World.txt';

        $data = vsprintf('data:text/plain;base64,%s', [
            base64_encode('Hello, PHP developer!'),
        ]);

        // Act: Parse Data With Name
        $file = parse_data($data, name: $name);

        // Assert: File Name Equals Name
        $this->assertEquals($name, $file->name);
        $this->assertNotEquals($name, $file->base);
    }

    public function testParsingFileWithoutNameUsesFileName(): void
    {
        // Arrange: Create Temp File
        $path = $this->createTempFile();

        assert(!empty($name = basename($path)));
        $this->assertStringEndsWith($name, $path);

        // Act: Parse Data With Null Name
        $file = parse_data($path, name: null, cleanup: true);

        // Assert: Both File Names Are Equal
        $this->assertEquals($name, $file->name);
        $this->assertNotEquals($file->name, $file->base);
    }

    public function testParsingFileDataCanDeleteFile(): void
    {
        // Arrange: Create Temp File
        $path = $this->createTempFile();
        $this->assertFileExists($path);

        // Act: Parse Data and Delete File
        $file = parse_data($path, cleanup: true);

        // Assert: Original File Is Deleted
        $this->assertFileExists($file->path);
        $this->assertFileDoesNotExist($path);
    }

    #[DataProvider('providerDataAndMetadata')]
    public function testParsingData(string $data, int $size, string $type): void
    {
        $file = parse_data($data);

        $this->assertFileExists($file->path);
        $this->assertEquals($size, $file->size);
        $this->assertEquals($type, $file->type);
    }

    /**
     * @return list<list<int|non-empty-string>>
     */
    public static function providerDataAndMetadata(): array
    {
        $provider = [
            ['data:,Test', 4, 'text/plain'],
            ['data:text/plain,Test', 4, 'text/plain'],
            ['data:text/plain;charset=US-ASCII,Hello%20world', 11, 'text/plain'],
            ['data:;base64,SGVsbG8sIHdvcmxkIQ==', 13, 'text/plain'],
            ['data:text/plain;base64,SGVsbG8sIHdvcmxkIQ==', 13, 'text/plain'],
            ['data:application/json,%7B%22id%22%3A10%7D', 9, 'application/json'],
            ['data:application/json;base64,eyJpZCI6MTB9', 9, 'application/json'],

            // 1x1 Transparent GIF
            ['data:image/gif;base64,R0lGODdhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==', 43, 'image/gif'],

            // @see https://stackoverflow.com/questions/17279712/what-is-the-smallest-possible-valid-pdf#comment59467299_17280876
            ['data:application/pdf;base64,JVBERi0xLg10cmFpbGVyPDwvUm9vdDw8L1BhZ2VzPDwvS2lkc1s8PC9NZWRpYUJveFswIDAgMyAzXT4+XT4+Pj4+Pg==', 67, 'application/pdf'],
        ];

        return $provider;
    }

    #[DataProvider('providerFileAndMetadata')]
    public function testParsingFile(string $data, int $size, string $type): void
    {
        $file = parse_data($data);

        $this->assertFileExists($file->path);
        $this->assertEquals($type, $file->type);
        $this->assertEquals($size, $file->size);
    }

    /**
     * @return list<list<int|non-empty-string>>
     */
    public static function providerFileAndMetadata(): array
    {
        return [
            [__DIR__.'/data/pdf-small.pdf', 36916, 'application/pdf'],
            [__DIR__.'/data/png-small.png', 10289, 'image/png'],
            [__DIR__.'/data/text-small.txt', 86, 'text/plain'],
            [__DIR__.'/data/word-small.docx', 6657, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ];
    }

    #[DataProvider('providerBase64DataAndMetadata')]
    public function testParsingBase64Data(string $data, int $size, string $type): void
    {
        $file = parse_base64_data($data, $type);

        $this->assertFileExists($file->path);
        $this->assertEquals($size, $file->size);
        $this->assertEquals($type, $file->type);
    }

    /**
     * @return list<list<int|non-empty-string>>
     */
    public static function providerBase64DataAndMetadata(): array
    {
        $provider = [
            ['eyJpZCI6MTB9', 9, 'application/json'],
            ['SGVsbG8sIHdvcmxkIQ==', 13, 'text/plain'],
            ['R0lGODdhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==', 43, 'image/gif'],
            ['iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVQImWNgAAIAAAUAAWJVMogAAAAASUVORK5CYII=', 68, 'image/png'],
        ];

        return $provider;
    }

    public function testParsingTextDataGeneratesNameIfNameIsEmpty(): void
    {
        $this->assertNotEmpty(parse_text_data('Hello, world!', '')->name);
    }

    public function testParsingTextDataGeneratesNameIfNameExtensionIsNotDotTxt(): void
    {
        $name = 'parse_text_example';
        $file = parse_text_data('Hello, world!', $name);

        $this->assertNotEquals($name, $file->name);
        $this->assertStringEndsWith('.txt', $file->name);
    }

    public function testParsingTextData(): void
    {
        $name = 'parse_text_example.txt';
        $text = 'Hello, PHP developer!';

        $file = parse_text_data($text, $name);

        $this->assertFileExists($file->path);
        $this->assertEquals($name, $file->name);
        $this->assertEquals($text, $file->read());
    }
}
