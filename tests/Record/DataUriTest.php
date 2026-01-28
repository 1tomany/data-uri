<?php

namespace OneToMany\DataUri\Tests\Record;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\Record\DataUriInterface;
use OneToMany\DataUri\DataDecoder;
use OneToMany\DataUri\Exception\RuntimeException;
use OneToMany\DataUri\Record\DataUri;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

use function base64_encode;

final class DataUriTest extends TestCase
{
    public function testDestructorDeletesTemporaryFile(): void
    {
        $file = $this->decodeFile();
        $this->assertFileExists($file->getPath());

        $file->__destruct();
        $this->assertFileDoesNotExist($file->getPath());
    }

    public function testDestructorDoesNotDeleteTemporaryFileWhenFileDoesNotExist(): void
    {
        // Arrange
        $file = $this->decodeFile();
        $this->assertFileExists($file->getPath());

        new Filesystem()->remove($file->getPath());
        $this->assertFileDoesNotExist($file->getPath());

        // Act
        $file->__destruct();

        // Assert
        $this->assertFileDoesNotExist($file->getPath());
    }

    public function testToStringReturnsPath(): void
    {
        // Arrange
        $file = $this->decodeFile();

        // Act
        $toString = $file->__toString();

        // Assert
        $this->assertEquals($file->getPath(), $toString);
    }

    public function testReadingFileRequiresFileToExist(): void
    {
        // Arrange
        $file = $this->decodeFile();

        new Filesystem()->remove($file->getPath());
        $this->assertFileDoesNotExist($file->getPath());

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Reading the file "'.$file->getPath().'" failed.');

        // Act
        $file->read();
    }

    public function testToBase64RequiresFileToExist(): void
    {
        // Arrange
        $file = $this->decodeFile();

        new Filesystem()->remove($file->getPath());
        $this->assertFileDoesNotExist($file->getPath());

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Encoding the file "'.$file->getPath().'" failed.');

        // Act
        $file->toBase64();
    }

    public function testToBase64(): void
    {
        // Arrange
        $file = $this->decodeFile();

        // Act
        $base64 = $file->toBase64();

        // Assert
        $this->assertSame($base64, base64_encode(new Filesystem()->readFile($file->getPath())));
    }

    public function testToDataUriRequiresFileToExist(): void
    {
        // Arrange
        $file = $this->decodeFile();

        new Filesystem()->remove($file->getPath());
        $this->assertFileDoesNotExist($file->getPath());

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Generating the URI of the file "'.$file->getPath().'" failed.');

        // Act
        $file->toUri();
    }

    public function testFilesWithDifferentHashesAreNotEqual(): void
    {
        $file1 = $this->decodeFile('pdf-small.pdf');
        $file2 = $this->decodeFile('png-small.png');

        $this->assertFalse($file1->equals($file2));
        $this->assertFalse($file2->equals($file1));
        $this->assertNotEquals($file1->getHash(), $file2->getHash());
    }

    public function testSmartFilesWithIdenticalHashesAreLooselyEqual(): void
    {
        $file1 = $this->decodeFile('pdf-small.pdf');
        $file2 = $this->decodeFile('pdf-small.pdf');

        $this->assertTrue($file1->equals($file2, false));
        $this->assertTrue($file2->equals($file1, false));
        $this->assertEquals($file1->getHash(), $file2->getHash());
        $this->assertNotEquals($file1->getPath(), $file2->getPath());
    }

    public function testSmartFilesWithIdenticalHashesAndPathsAreStrictlyEqual(): void
    {
        $file = $this->decodeFile();

        $this->assertTrue($file->equals($file, true));
    }

    /**
     * @param non-empty-string $path
     */
    private function decodeFile(string $path = 'pdf-small.pdf'): DataUri
    {
        /** @var DataUri&DataUriInterface $file */
        $file = new DataDecoder()->decode(__DIR__.'/../data/'.$path);

        return $file;
    }
}
