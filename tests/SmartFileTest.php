<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\Record\SmartFileInterface;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\SmartFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

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

        new SmartFile('', '/path/to/file.txt', '', null, Type::Text, 'text/plain', 0, ''); // @phpstan-ignore-line
    }

    public function testConstructorRequiresValidHashLength(): void
    {
        $hash = 'h';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The hash "'.$hash.'" must be '.SmartFileInterface::MINIMUM_HASH_LENGTH.' or more characters.');

        new SmartFile($hash, 'path', '', null, Type::Text, 'text/plain', 0, ''); // @phpstan-ignore-line
    }

    public function testConstructorRequiresNonEmptyPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The path cannot be empty.');

        new SmartFile('hash', '', '', null, Type::Text, 'text/plain', 0, ''); // @phpstan-ignore-line
    }

    public function testConstructorRequiresPathToBeAFile(): void
    {
        $this->assertDirectoryExists($path = __DIR__);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The path "'.$path.'" is not a file.');

        new SmartFile('hash', $path, '', null, Type::Text, 'text/plain', 0, ''); // @phpstan-ignore-line
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
        $file = new SmartFile('hash', $path, '', null, Type::Text, 'text/plain', 0, '', false); // @phpstan-ignore-line

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

    private function createTemporarySmartFile(?string $contents = null): SmartFile
    {
        // Arrange: Create a local temporary file
        $path = $this->createTempFile(contents: $contents);

        // Arrange: Create a non-empty file name
        $name = basename($path) ?: bin2hex(random_bytes(6));

        return new SmartFile('hash', $path, $name, 'txt', Type::Text, 'text/plain', filesize($path) ?: 0, $name, true);
    }

    /**
     * @param non-empty-lowercase-string $hash
     */
    private function createSmartFileWithHash(string $hash): SmartFile
    {
        return new SmartFile($hash, __DIR__.'/data/pdf-small.pdf', 'pdf-small.pdf', null, Type::Pdf, 'application/pdf', 36916, 'pdf-small.pdf', false);
    }
}
