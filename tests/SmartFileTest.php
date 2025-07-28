<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use OneToMany\DataUri\SmartFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;

use function basename;
use function unlink;

#[Group('UnitTests')]
final class SmartFileTest extends TestCase
{
    use TestFileTrait;

    public function testConstructorRequiresNonEmptyHash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The hash cannot be empty.');

        new SmartFile('', '/path/to/file.txt', null, 'text/plain');
    }

    public function testConstructorRequiresNonEmptyPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The path cannot be empty.');

        new SmartFile('hash', '', null, 'text/plain');
    }

    public function testConstructorSetsNameToFileNameWhenNameIsNull(): void
    {
        // Arrange: Create temp file
        $path = $this->createTempFile();

        // Act: Construct SmartFile with null name
        $file = new SmartFile('hash', $path, null, 'text/plain', null, true, true);

        // Assert: Name is set to actual file name
        $this->assertEquals(basename($path), $file->name);
    }

    public function testConstructorRequiresFileToExistWhenCheckPathIsTrue(): void
    {
        // Arrange: Create invalid file path
        $path = '/invalid/path/to/file.txt';
        $this->assertFileDoesNotExist($path);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The file "'.$path.'" does not exist.');

        new SmartFile('hash', $path, null, 'text/plain', null, true, false);
    }

    public function testConstructorRequiresPathToBeAFileWhenCheckPathIsTrue(): void
    {
        $this->assertDirectoryExists($path = __DIR__);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The path "'.$path.'" is not a file.');

        new SmartFile('hash', $path, null, 'text/plain', null, true, false);
    }

    public function testConstructorGeneratesKeyWithoutExtension(): void
    {
        // Arrange: Create temp file
        $path = $this->createTempFile('');

        // Assert: File has no extension
        $this->assertEmpty(Path::getExtension($path));

        // Act: Construct SmartFile using file without extension
        $file = new SmartFile('hash', $path, null, 'text/plain', null, true, true);

        // Assert: Key has no extension
        $this->assertEmpty(Path::getExtension($file->key));
    }

    public function testConstructorGeneratesKeyWithExtension(): void
    {
        // Arrange: Create temp file
        $path = $this->createTempFile();

        // Assert: File has extension
        $this->assertNotEmpty(Path::getExtension($path));

        // Act: Construct SmartFile using file with extension
        $file = new SmartFile('hash', $path, null, 'text/plain', null, true, true);

        // Assert: Remote key generated with extension
        $this->assertNotEmpty(Path::getExtension($file->key));
    }

    public function testDestructorDeletesTemporaryFileWhenDeleteIsTrue(): void
    {
        // Arrange: Create temp file
        $path = $this->createTempFile();

        // Act: Construct SmartFile to self destruct
        $file = new SmartFile('hash', $path, null, 'text/plain', null, true, true);

        // Assert: SmartFile set to delete
        $this->assertTrue($file->delete);
        $this->assertFileExists($file->path);

        // Act: Self destruct
        $file->__destruct();

        // Assert: Destructor deleted file
        $this->assertFileDoesNotExist($file->path);
    }

    public function testDestructorDoesNotDeleteTemporaryFileWhenFileAlreadyDeleted(): void
    {
        // Arrange: Create temp file
        $path = $this->createTempFile();

        // Act: Construct SmartFile to self destruct
        $file = new SmartFile('hash', $path, null, 'text/plain', null, true, true);

        // Assert: SmartFile to delete
        $this->assertTrue($file->delete);
        $this->assertFileExists($file->path);

        // Act: Manually delete file
        $this->assertTrue(unlink($file->path));
        $this->assertFileDoesNotExist($file->path);

        // Act: Self destruct
        $file->__destruct();

        // Assert: Destructor can not delete file
        $this->assertFileDoesNotExist($file->path);
    }

    public function testDestructorDoesNotDeleteTemporaryFileWhenDestroyIsFalse(): void
    {
        // Arrange: Create temp file
        $path = $this->createTempFile();

        // Act: Construct SmartFile to self destruct
        $file = new SmartFile('hash', $path, null, 'text/plain', null, true, false);

        // Assert: SmartFile to not delete
        $this->assertFalse($file->delete);
        $this->assertFileExists($file->path);

        // Act: Self destruct
        $file->__destruct();

        // Assert: Destructor ignored file
        $this->assertFileExists($file->path);
        $this->assertTrue(unlink($file->path));
    }

    public function testToStringReturnsPath(): void
    {
        // Arrange: Create temp file
        $path = $this->createTempFile();

        // Act: Construct SmartFile with valid path
        $file = new SmartFile('hash', $path, null, 'text/plain', null, true, true);

        // Assert: Path equals string representation
        $this->assertEquals($path, $file->__toString());
    }

    public function testReadingFileRequiresFileToExist(): void
    {
        // Arrange: Create invalid file path
        $path = '/invalid/path/to/file.txt';
        $this->assertFileDoesNotExist($path);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to read the file "'.$path.'".');

        // Act: Construct SmartFile and attempt to read the file
        new SmartFile('hash', $path, null, 'text/plain', null, false, true)->read();
    }

    public function testReadingEmptyFile(): void
    {
        // Arrange: Create temp file
        $path = $this->createTempFile();

        // Arrange: Construct empty SmartFile
        $file = new SmartFile('hash', $path, null, 'text/plain', null, true, true);

        // Assert: File is empty
        $this->assertEmpty($file->read());
    }

    public function testToDataUriRequiresFileToExist(): void
    {
        // Arrange: Create invalid file path
        $path = '/invalid/path/to/file.txt';
        $this->assertFileDoesNotExist($path);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to encode the file "'.$path.'".');

        // Act: Construct SmartFile and attempt to generate the data uri
        new SmartFile('hash', $path, null, 'text/plain', null, false, true)->toDataUri();
    }

    public function testSmartFilesWithDifferentHashesAreNotEqual(): void
    {
        $file1 = new SmartFile('hash1', '1.txt', null, 'text/plain', null, false, false);
        $file2 = new SmartFile('hash2', '2.txt', null, 'text/plain', null, false, false);

        $this->assertFalse($file1->equals($file2));
        $this->assertFalse($file2->equals($file1));
    }

    public function testSmartFilesWithIdenticalHashesAreLooselyEqual(): void
    {
        $file1 = new SmartFile('hash1', '1.txt', null, 'text/plain', null, false, false);
        $file2 = new SmartFile('hash1', '2.txt', null, 'text/plain', null, false, false);

        $this->assertTrue($file1->equals($file2));
        $this->assertTrue($file2->equals($file1));
    }

    public function testSmartFilesWithIdenticalHashesAndPathsAreStrictlyEqual(): void
    {
        $file1 = new SmartFile('hash1', '1.txt', null, 'text/plain', null, false, false);
        $file2 = new SmartFile('hash1', '1.txt', null, 'text/plain', null, false, false);

        $this->assertTrue($file1->equals($file2, true));
        $this->assertTrue($file2->equals($file1, true));
    }
}
