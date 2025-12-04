<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Contract\Record\SmartFileInterface;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use OneToMany\DataUri\SmartFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;

use function base64_encode;
use function basename;
use function bin2hex;
use function filesize;
use function OneToMany\DataUri\parse_data;
use function random_bytes;
use function random_int;
use function unlink;

#[Group('UnitTests')]
final class SmartFileTest extends TestCase
{
    use TestFileTrait;

    protected function tearDown(): void
    {
        $this->cleanupTempFiles();
    }

    public function testConstructorRequiresNonEmptyHash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The hash cannot be empty.');

        new SmartFile('', '/path/to/file.txt', null, 'text/plain');
    }

    public function testConstructorRequiresValidHashLength(): void
    {
        $hash = 'h';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The hash "'.$hash.'" must be '.SmartFileInterface::MINIMUM_HASH_LENGTH.' or more characters.');

        new SmartFile($hash, 'path', 'file', 'text/plain', 0, false, true);
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

    public function testConstructorSetsMimeTypeForJsonLinesFiles(): void
    {
        $path = $this->createTempFile('.jsonl', '{"id": 10}');

        $file = new SmartFile('hash', $path, null, 'text/plain');
        $this->assertEquals('application/jsonl', $file->getMimeType());
    }

    public function testConstructorRequiresNonEmptyMimeType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The MIME type cannot be empty.');

        new SmartFile('hash', 'file.txt', null, '', null, false, false);
    }

    public function testConstructorRequiresLooselyValidMimeType(): void
    {
        $mimeType = 'invalid_mime_type';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The MIME type "'.$mimeType.'" is not valid.');

        new SmartFile('hash', 'file.txt', null, $mimeType, null, false, false);
    }

    public function testConstructorSetsSizeToZeroWhenSizeIsNullAndCheckPathIsFalse(): void
    {
        $this->assertSame(0, new SmartFile('hash', 'file.txt', null, 'text/plain', null, false, false)->size);
    }

    public function testConstructorSetsSizeWhenSizeIsNullAndCheckPathIsTrue(): void
    {
        // Arrange: Create temp file
        $path = $this->createTempFile(contents: bin2hex(random_bytes(random_int(100, 1000))));

        // Assert: Filesize is calculated
        $this->assertSame(filesize($path), new SmartFile('hash', $path, null, 'text/plain', null, true, true)->size);
    }

    public function testConstructorGeneratesRemoteKeyWithoutExtension(): void
    {
        // Arrange: Create temp file
        $path = $this->createTempFile('');

        // Assert: File has no extension
        $this->assertEmpty(Path::getExtension($path));

        // Act: Construct SmartFile using file without extension
        $file = new SmartFile('hash', $path, null, 'text/plain', null, true, true);

        // Assert: Key has no extension
        $this->assertEmpty(Path::getExtension($file->remoteKey));
    }

    public function testConstructorGeneratesRemoteKeyWithExtension(): void
    {
        // Arrange: Create temp file
        $path = $this->createTempFile();

        // Assert: File has extension
        $this->assertNotEmpty(Path::getExtension($path));

        // Act: Construct SmartFile using file with extension
        $file = new SmartFile('hash', $path, null, 'text/plain', null, true, true);

        // Assert: Remote key generated with extension
        $this->assertNotEmpty(Path::getExtension($file->remoteKey));
    }

    public function testDestructorDeletesTemporaryFileWhenAutoDeleteIsTrue(): void
    {
        // Arrange: Create temp file
        $path = $this->createTempFile();

        // Act: Construct SmartFile to auto delete
        $file = new SmartFile('hash', $path, null, 'text/plain', null, true, true);

        // Assert: SmartFile set to auto delete
        $this->assertTrue($file->autoDelete);
        $this->assertFileExists($file->path);

        // Act: Self destruct
        $file->__destruct();

        // Assert: Destructor deleted file
        $this->assertFileDoesNotExist($file->path);
    }

    public function testDestructorDoesNotDeleteTemporaryFileWhenFileDoesNotExistDeleted(): void
    {
        // Arrange: Create temp file
        $path = $this->createTempFile();

        // Act: Construct SmartFile to auto delete
        $file = new SmartFile('hash', $path, null, 'text/plain', null, true, true);

        // Assert: SmartFile to auto delete
        $this->assertTrue($file->autoDelete);
        $this->assertFileExists($file->path);

        // Act: Manually delete file
        $this->assertTrue(unlink($file->path));
        $this->assertFileDoesNotExist($file->path);

        // Act: Self destruct
        $file->__destruct();

        // Assert: Destructor can not delete file
        $this->assertFileDoesNotExist($file->path);
    }

    public function testDestructorDoesNotDeleteTemporaryFileWhenAutoDeleteIsFalse(): void
    {
        // Arrange: Create temp file
        $path = $this->createTempFile();

        // Act: Construct SmartFile to not auto delete
        $file = new SmartFile('hash', $path, null, 'text/plain', null, true, false);

        // Assert: SmartFile to not auto delete
        $this->assertFalse($file->autoDelete);
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

    public function testToBase64RequiresFileToExist(): void
    {
        // Arrange: Create invalid file path
        $path = '/invalid/path/to/file.txt';
        $this->assertFileDoesNotExist($path);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to encode the file "'.$path.'".');

        // Act: Construct SmartFile and attempt to encode it as base64
        new SmartFile('hash', $path, null, 'text/plain', null, false, true)->toBase64();
    }

    public function testToBase64(): void
    {
        $contents = 'Hello, world!';

        // Arrange: Create non-empty temp file
        $path = $this->createTempFile(contents: $contents);

        // Arrange: Construct non-empty SmartFile
        $file = parse_data($path, deleteOriginal: true);

        // Assert: Base64 encodings are identical
        $this->assertSame(base64_encode($contents), $file->toBase64());
    }

    public function testToDataUriRequiresFileToExist(): void
    {
        // Arrange: Create invalid file path
        $path = '/invalid/path/to/file.txt';
        $this->assertFileDoesNotExist($path);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to generate a data URI representation of the file "'.$path.'".');

        // Act: Construct SmartFile and attempt to generate the data URI representation
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
