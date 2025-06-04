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

use function base64_encode;
use function basename;
use function OneToMany\DataUri\parse_data;
use function random_bytes;
use function sys_get_temp_dir;
use function vsprintf;

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
        $this->assertNotEquals($name, $file->basename);
    }

    public function testParsingFileWithoutNameUsesFileName(): void
    {
        // Arrange: Create Temp File
        $path = $this->createTempFile();

        $name = basename($path);
        $this->assertStringEndsWith($name, $path);

        // Act: Parse Data With Null Name
        $file = parse_data($path, name: null, delete: true);

        // Assert: Both File Names Are Equal
        $this->assertEquals($name, $file->name);
        $this->assertNotEquals($file->name, $file->basename);
    }

    public function testParsingFileDataCanDeleteFile(): void
    {
        // Arrange: Create Temp File
        $path = $this->createTempFile();
        $this->assertFileExists($path);

        // Act: Parse Data and Delete File
        $file = parse_data($path, delete: true);

        // Assert: Original File Is Deleted
        $this->assertFileExists($file->path);
        $this->assertFileDoesNotExist($path);
    }

    #[DataProvider('providerDataAndMetadata')]
    public function testParsingData(string $data, string $hash, string $type, int $size): void
    {
        $file = parse_data($data);

        $this->assertFileExists($file->path);
        $this->assertEquals($hash, $file->hash);
        $this->assertEquals($type, $file->type);
        $this->assertEquals($size, $file->size);
    }

    /**
     * @return list<list<int|non-empty-string>>
     */
    public static function providerDataAndMetadata(): array
    {
        $provider = [
            ['data:,Test', '532eaabd9574880dbf76b9b8cc00832c20a6ec113d682299550d7a6e0f345e25', 'text/plain', 4],
            ['data:text/plain,Test', '532eaabd9574880dbf76b9b8cc00832c20a6ec113d682299550d7a6e0f345e25', 'text/plain', 4],
            ['data:text/plain;charset=US-ASCII,Hello%20world', '64ec88ca00b268e5ba1a35678a1b5316d212f4f366b2477232534a8aeca37f3c', 'text/plain', 11],
            ['data:;base64,SGVsbG8sIHdvcmxkIQ==', '315f5bdb76d078c43b8ac0064e4a0164612b1fce77c869345bfc94c75894edd3', 'text/plain', 13],
            ['data:text/plain;base64,SGVsbG8sIHdvcmxkIQ==', '315f5bdb76d078c43b8ac0064e4a0164612b1fce77c869345bfc94c75894edd3', 'text/plain', 13],
            ['data:application/json,%7B%22id%22%3A10%7D', '451973eae50e0e18bc1e8a4bd66bf035306c435164af0b41ceaf3a0ab918bd08', 'application/json', 9],
            ['data:application/json;base64,eyJpZCI6MTB9', '451973eae50e0e18bc1e8a4bd66bf035306c435164af0b41ceaf3a0ab918bd08', 'application/json', 9],

            // @see https://stackoverflow.com/questions/17279712/what-is-the-smallest-possible-valid-pdf#comment59467299_17280876
            ['data:application/pdf;base64,JVBERi0xLg10cmFpbGVyPDwvUm9vdDw8L1BhZ2VzPDwvS2lkc1s8PC9NZWRpYUJveFswIDAgMyAzXT4+XT4+Pj4+Pg==', '1d9e024228ddfa3a9b8924b96d65d7428c326cc8945102a8149182b2f8e823b5', 'application/pdf', 67],
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
