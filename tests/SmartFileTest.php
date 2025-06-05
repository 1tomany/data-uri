<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use OneToMany\DataUri\SmartFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

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

        new SmartFile('', '/path/to/file.txt', null, null, 'text/plain');
    }

    public function testConstructorRequiresNonEmptyPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The path cannot be empty.');

        new SmartFile('hash', '', null, null, 'text/plain');
    }

    public function testConstructorSetsNameToFileNameWhenNameIsNull(): void
    {
        // Arrange: Create Temp File
        $path = $this->createTempFile();

        // Act: Construct SmartFile With Null Name
        $file = new SmartFile('hash', $path, null, null, 'text/plain', true, true);

        // Assert: Name is Set to File Name
        $this->assertEquals(basename($path), $file->name);
    }

    public function testConstructorRequiresFileToExistWhenCheckPathIsTrue(): void
    {
        // Arrange: Create Invalid File Path
        $path = '/invalid/path/to/file.txt';
        $this->assertFileDoesNotExist($path);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The file "'.$path.'" does not exist.');

        new SmartFile('hash', $path, null, null, 'text/plain', true, false);
    }

    public function testConstructorRequiresPathToBeAFileWhenCheckPathIsTrue(): void
    {
        $this->assertDirectoryExists($path = __DIR__);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The path "'.$path.'" is not a file.');

        new SmartFile('hash', $path, null, null, 'text/plain', true, false);
    }

    public function testConstructorGeneratesKey(): void
    {
        // Arrange: Create Temp File
        $path = $this->createTempFile();

        // Act: Construct SmartFile With Null Remote Key
        $file = new SmartFile('hash', $path, null, null, 'text/plain', true, true);

        // Assert: Remote Key Generated
        $this->assertMatchesRegularExpression('/^[a-z0-9]{2}\/[a-z0-9]{2}\/[a-z0-9]+\.[a-z0-9]+$/', $file->key);
    }

    public function testDestructorDeletesTemporaryFileWhenDeleteIsTrue(): void
    {
        // Arrange: Create Temp File
        $path = $this->createTempFile();

        // Act: Construct SmartFile to Self Destruct
        $file = new SmartFile('hash', $path, null, null, 'text/plain', true, true);

        // Assert: SmartFile Set to Delete
        $this->assertTrue($file->delete);
        $this->assertFileExists($file->path);

        // Act: Self Destruct
        $file->__destruct();

        // Assert: Destructor Deleted File
        $this->assertFileDoesNotExist($file->path);
    }

    public function testDestructorDoesNotDeleteTemporaryFileWhenFileAlreadyDeleted(): void
    {
        // Arrange: Create Temp File
        $path = $this->createTempFile();

        // Act: Construct SmartFile to Self Destruct
        $file = new SmartFile('hash', $path, null, null, 'text/plain', true, true);

        // Assert: SmartFile to Delete
        $this->assertTrue($file->delete);
        $this->assertFileExists($file->path);

        // Act: Manually Delete File
        $this->assertTrue(unlink($file->path));
        $this->assertFileDoesNotExist($file->path);

        // Act: Self Destruct
        $file->__destruct();

        // Assert: Destructor Can Not Delete File
        $this->assertFileDoesNotExist($file->path);
    }

    public function testDestructorDoesNotDeleteTemporaryFileWhenDestroyIsFalse(): void
    {
        // Arrange: Create Temp File
        $path = $this->createTempFile();

        // Act: Construct SmartFile to Self Destruct
        $file = new SmartFile('hash', $path, null, null, 'text/plain', true, false);

        // Assert: SmartFile to Not Delete
        $this->assertFalse($file->delete);
        $this->assertFileExists($file->path);

        // Act: Self Destruct
        $file->__destruct();

        // Assert: Destructor Ignored File
        $this->assertFileExists($file->path);
        $this->assertTrue(unlink($file->path));
    }

    public function testToStringReturnsPath(): void
    {
        // Arrange: Create Temp File
        $path = $this->createTempFile();

        // Act: Construct SmartFile With Valid Path
        $file = new SmartFile('hash', $path, null, null, 'text/plain', true, true);

        // Assert: Path Equals String Representation
        $this->assertEquals($path, $file->__toString());
    }

    public function testReadingFileRequiresFileToExist(): void
    {
        // Arrange: Create Invalid File Path
        $path = '/invalid/path/to/file.txt';
        $this->assertFileDoesNotExist($path);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to read the file "'.$path.'".');

        // Act: Construct SmartFile and Attempt to Read the File
        new SmartFile('hash', $path, null, null, 'text/plain', false, true)->read();
    }

    public function testReadingEmptyFile(): void
    {
        // Arrange: Create Temp File
        $path = $this->createTempFile();

        // Arrange: Construct SmartFile
        $file = new SmartFile('hash', $path, null, null, 'text/plain', true, true);

        $this->assertEmpty($file->read());
    }

    public function testToDataUriRequiresFileToExist(): void
    {
        // Arrange: Create Invalid File Path
        $path = '/invalid/path/to/file.txt';
        $this->assertFileDoesNotExist($path);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to encode the file "'.$path.'".');

        // Act: Construct SmartFile and Attempt to Generate the Data URI
        new SmartFile('hash', $path, null, null, 'text/plain', false, true)->toDataUri();
    }

    public function testSmartFilesWithDifferentHashesAreNotEqual(): void
    {
        $file1 = new SmartFile('hash1', '1.txt', null, null, 'text/plain', false, false);
        $file2 = new SmartFile('hash2', '2.txt', null, null, 'text/plain', false, false);

        $this->assertFalse($file1->equals($file2));
        $this->assertFalse($file2->equals($file1));
    }

    public function testSmartFilesWithIdenticalHashesAreLooselyEqual(): void
    {
        $file1 = new SmartFile('hash1', '1.txt', null, null, 'text/plain', false, false);
        $file2 = new SmartFile('hash1', '2.txt', null, null, 'text/plain', false, false);

        $this->assertTrue($file1->equals($file2));
        $this->assertTrue($file2->equals($file1));
    }

    public function testSmartFilesWithIdenticalHashesAndPathsAreStrictlyEqual(): void
    {
        $file1 = new SmartFile('hash1', '1.txt', null, null, 'text/plain', false, false);
        $file2 = new SmartFile('hash1', '1.txt', null, null, 'text/plain', false, false);

        $this->assertTrue($file1->equals($file2, true));
        $this->assertTrue($file2->equals($file1, true));
    }
}
