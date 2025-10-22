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
        // Arrange: Create unreadable virtual file
        $file = vfsStream::newFile('Invoice_1984.pdf');
        $file->chmod(0400)->chown(vfsStream::OWNER_ROOT);

        vfsStream::setup(structure: [$file]);

        // Assert: Virtual file is not readable
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
        // Arrange: Writable directory
        $directory = sys_get_temp_dir();

        $this->assertDirectoryExists($directory);
        $this->assertDirectoryIsWritable($directory);

        // Assert: Failed to create temporary file
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create a file in "'.$directory.'".');

        // Arrange: Mock filesystem library
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())->method('tempnam')->willThrowException(new IOException('Error'));

        // Act: Parse data with expected exception
        parse_data(__DIR__.'/data/pdf-small.pdf', directory: $directory, filesystem: $filesystem);
    }

    public function testParsingDataCanSetName(): void
    {
        // Arrange: Name and data
        $name = 'Hello_World.txt';

        $data = vsprintf('data:text/plain;base64,%s', [
            base64_encode('Hello, PHP developer!'),
        ]);

        // Act: Parse data with name
        $file = parse_data($data, name: $name);

        // Assert: File name equals name
        $this->assertEquals($name, $file->name);
        $this->assertNotEquals($name, $file->basename);
    }

    public function testParsingFileWithoutNameUsesFileName(): void
    {
        // Arrange: Create temp file
        $path = $this->createTempFile();

        assert(!empty($name = basename($path)));
        $this->assertStringEndsWith($name, $path);

        // Act: Parse data with null name
        $file = parse_data($path, name: null, deleteOriginal: true);

        // Assert: Both file names are equal
        $this->assertEquals($name, $file->name);
        $this->assertNotEquals($file->name, $file->basename);
    }

    public function testParsingFileDataCanDeleteFile(): void
    {
        // Arrange: Create temp file
        $path = $this->createTempFile();
        $this->assertFileExists($path);

        // Act: Parse data and delete file
        $file = parse_data($path, deleteOriginal: true);

        // Assert: Original file is deleted
        $this->assertFileExists($file->path);
        $this->assertFileDoesNotExist($path);
    }

    #[DataProvider('providerDataAndMetadata')]
    public function testParsingData(string $data, string $mimeType, int $size): void
    {
        $file = parse_data($data);

        $this->assertFileExists($file->path);
        $this->assertEquals($mimeType, $file->mimeType);
        $this->assertEquals($size, $file->size);
    }

    /**
     * @return list<list<int|non-empty-string>>
     */
    public static function providerDataAndMetadata(): array
    {
        $provider = [
            ['data:,Test', 'text/plain', 4],
            ['data:text/plain,Test', 'text/plain', 4],
            ['data:text/plain;charset=US-ASCII,Hello%20world', 'text/plain', 11],
            ['data:;base64,SGVsbG8sIHdvcmxkIQ==', 'text/plain', 13],
            ['data:text/plain;base64,SGVsbG8sIHdvcmxkIQ==', 'text/plain', 13],
            ['data:application/json,%7B%22id%22%3A10%7D', 'application/json', 9],
            ['data:application/json;base64,eyJpZCI6MTB9', 'application/json', 9],

            // 1x1 Transparent GIF
            ['data:image/gif;base64,R0lGODdhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==', 'image/gif', 43],

            // @see https://stackoverflow.com/questions/17279712/what-is-the-smallest-possible-valid-pdf#comment59467299_17280876
            ['data:application/pdf;base64,JVBERi0xLg10cmFpbGVyPDwvUm9vdDw8L1BhZ2VzPDwvS2lkc1s8PC9NZWRpYUJveFswIDAgMyAzXT4+XT4+Pj4+Pg==', 'application/pdf', 67],
        ];

        return $provider;
    }

    #[DataProvider('providerFileAndMetadata')]
    public function testParsingFile(string $data, string $mimeType, int $size): void
    {
        $file = parse_data($data);

        $this->assertFileExists($file->path);
        $this->assertEquals($mimeType, $file->mimeType);
        $this->assertEquals($size, $file->size);
    }

    /**
     * @return list<list<int|non-empty-string>>
     */
    public static function providerFileAndMetadata(): array
    {
        return [
            [__DIR__.'/data/pdf-small.pdf', 'application/pdf', 36916],
            [__DIR__.'/data/png-small.png', 'image/png', 10289],
            [__DIR__.'/data/text-small.txt', 'text/plain', 86],
            [__DIR__.'/data/word-small.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 6657],
        ];
    }

    #[DataProvider('providerBase64DataAndMetadata')]
    public function testParsingBase64Data(string $data, string $mimeType, int $size): void
    {
        $file = parse_base64_data($data, $mimeType);

        $this->assertFileExists($file->path);
        $this->assertEquals($mimeType, $file->mimeType);
        $this->assertEquals($size, $file->size);
    }

    /**
     * @return list<list<int|non-empty-string>>
     */
    public static function providerBase64DataAndMetadata(): array
    {
        $provider = [
            ['eyJpZCI6MTB9', 'application/json', 9],
            ['SGVsbG8sIHdvcmxkIQ==', 'text/plain', 13],
            ['R0lGODdhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==', 'image/gif', 43],
            ['iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVQImWNgAAIAAAUAAWJVMogAAAAASUVORK5CYII=', 'image/png', 68],
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
