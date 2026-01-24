<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Contract\Enum\FileType;
use OneToMany\DataUri\Contract\Record\SmartFileInterface;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use OneToMany\DataUri\SmartFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

use function assert;
use function base64_encode;
use function basename;
use function bin2hex;
use function filesize;
use function random_bytes;

#[Group('UnitTests')]
final class SmartFileTest extends TestCase
{
    use TestFileTrait;

    public function testConstructorRequiresNonEmptyHash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The hash cannot be empty.');

        new SmartFile('', '/path/to/file.txt', '', null, FileType::Text, 'text/plain', 0, ''); // @phpstan-ignore-line
    }

    public function testConstructorRequiresValidHashLength(): void
    {
        $hash = 'h';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The hash "'.$hash.'" must be '.SmartFileInterface::MINIMUM_HASH_LENGTH.' or more characters.');

        new SmartFile($hash, 'path', '', null, FileType::Text, 'text/plain', 0, ''); // @phpstan-ignore-line
    }

    public function testConstructorRequiresNonEmptyPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The path cannot be empty.');

        new SmartFile('hash', '', '', null, FileType::Text, 'text/plain', 0, ''); // @phpstan-ignore-line
    }

    public function testConstructorRequiresPathToBeAFile(): void
    {
        $this->assertDirectoryExists($path = __DIR__);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The path "'.$path.'" is not a file.');

        new SmartFile('hash', $path, '', null, FileType::Text, 'text/plain', 0, ''); // @phpstan-ignore-line
    }

    public function testDestructorDeletesTemporaryFileWhenAutoDeleteIsTrue(): void
    {
        // Act: Construct a valid SmartFile
        $file = $this->createTemporarySmartFile();

        // Assert: SmartFile set to auto delete
        $this->assertTrue($file->autoDelete);
        $this->assertFileExists($file->path);

        // Act: Self destruct
        $file->__destruct();

        // Assert: Destructor deleted file
        $this->assertFileDoesNotExist($file->path);
    }

    public function testDestructorDoesNotDeleteTemporaryFileWhenFileDoesNotExist(): void
    {
        // Act: Construct a valid SmartFile
        $file = $this->createTemporarySmartFile();

        // Assert: SmartFile to auto delete
        $this->assertTrue($file->autoDelete);
        $this->assertFileExists($file->path);

        // Arrange: Manually delete the file
        new Filesystem()->remove($file->path);

        // Assert: File was deleted from filesystem
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

        // Act: Construct a SmartFile to not auto delete
        $file = new SmartFile('hash', $path, '', null, FileType::Text, 'text/plain', 0, '', false); // @phpstan-ignore-line

        // Assert: SmartFile to not auto delete
        $this->assertFalse($file->autoDelete);
        $this->assertFileExists($file->path);

        // Act: Self destruct
        $file->__destruct();

        // Assert: Destructor ignored file
        $this->assertFileExists($file->path);

        // Arrange: Manually delete the file
        new Filesystem()->remove($file->path);

        // Assert: File was deleted from filesystem
        $this->assertFileDoesNotExist($file->path);
    }

    public function testToStringReturnsPath(): void
    {
        // Act: Construct a valid SmartFile
        $file = $this->createTemporarySmartFile();

        // Assert: Path equals string representation
        $this->assertEquals($file->path, $file->__toString());
    }

    public function testReadingFileRequiresFileToExist(): void
    {
        // Act: Construct a valid SmartFile
        $file = $this->createTemporarySmartFile();

        // Arrange: Manually delete the file
        new Filesystem()->remove($file->path);

        // Assert: File was deleted from filesystem
        $this->assertFileDoesNotExist($file->path);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to read the file "'.$file->path.'".');

        $file->read(); // Act: Attempt to read the file
    }

    public function testReadingEmptyFile(): void
    {
        // Act: Construct a valid SmartFile
        $file = $this->createTemporarySmartFile();

        // Assert: File is empty
        $this->assertEmpty($file->read());
    }

    public function testToBase64RequiresFileToExist(): void
    {
        // Act: Construct a valid SmartFile
        $file = $this->createTemporarySmartFile();

        // Arrange: Manually delete the file
        new Filesystem()->remove($file->path);

        // Assert: File was deleted from filesystem
        $this->assertFileDoesNotExist($file->path);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to encode the file "'.$file->path.'".');

        $file->toBase64(); // Act: Attempt to encode the file as base64
    }

    public function testToBase64(): void
    {
        // Arrange: Contents to encode
        $contents = 'Hello, world!';

        // Act: Construct a valid SmartFile
        $file = $this->createTemporarySmartFile($contents);

        // Assert: Base64 encodings are identical
        $this->assertSame(base64_encode($contents), $file->toBase64());
    }

    public function testToDataUriRequiresFileToExist(): void
    {
        // Act: Construct a valid SmartFile
        $file = $this->createTemporarySmartFile();

        // Arrange: Manually delete the file
        new Filesystem()->remove($file->path);

        // Assert: File was deleted from filesystem
        $this->assertFileDoesNotExist($file->path);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to generate a data URI representation of the file "'.$file->path.'".');

        $file->toDataUri(); // Act: Attempt to generate the data URI
    }

    public function testSmartFilesWithDifferentHashesAreNotEqual(): void
    {
        $file1 = $this->createSmartFileWithHash('hash1');
        $file2 = $this->createSmartFileWithHash('hash2');

        $this->assertFalse($file1->equals($file2));
        $this->assertFalse($file2->equals($file1));
    }

    public function testSmartFilesWithIdenticalHashesAreLooselyEqual(): void
    {
        $file1 = $this->createSmartFileWithHash('hash1');
        $file2 = $this->createSmartFileWithHash('hash1');

        $this->assertTrue($file1->equals($file2));
        $this->assertTrue($file2->equals($file1));
    }

    public function testSmartFilesWithIdenticalHashesAndPathsAreStrictlyEqual(): void
    {
        $file1 = $this->createSmartFileWithHash('hash1');
        $file2 = $this->createSmartFileWithHash('hash1');

        $this->assertSame($file1->path, $file2->path);
        $this->assertTrue($file1->equals($file2, true));
        $this->assertTrue($file2->equals($file1, true));
    }

    private function createTemporarySmartFile(?string $contents = null): SmartFile
    {
        // Arrange: Create a local temporary file
        $path = $this->createTempFile(contents: $contents);

        // Arrange: Create a non-empty file name
        $name = basename($path) ?: bin2hex(random_bytes(6));

        return new SmartFile('hash', $path, $name, 'txt', FileType::Text, 'text/plain', filesize($path) ?: 0, $name, true);
    }

    /**
     * @param non-empty-lowercase-string $hash
     */
    private function createSmartFileWithHash(string $hash): SmartFile
    {
        return new SmartFile($hash, __DIR__.'/data/pdf-small.pdf', 'pdf-small.pdf', null, FileType::Pdf, 'application/pdf', 36916, 'pdf-small.pdf', false);
    }
}
