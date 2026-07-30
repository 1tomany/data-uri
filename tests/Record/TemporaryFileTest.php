<?php

namespace OneToMany\DataUri\Tests\Record;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\Record\TemporaryFileInterface;
use OneToMany\DataUri\DataDecoder;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use OneToMany\DataUri\Record\TemporaryFile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function base64_encode;
use function basename;
use function dirname;
use function realpath;
use function sys_get_temp_dir;

final class TemporaryFileTest extends TestCase
{
    public function testConstructorRequiresNonEmptyPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The path cannot be empty.');

        new TemporaryFile('', null, 'php-logo.png', 10289, Type::Png);
    }

    public function testConstructorRequiresPathToNotBeDirectory(): void
    {
        $path = realpath(__DIR__.'/../../config/files/');

        $this->assertIsString($path);
        $this->assertDirectoryExists($path);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The path "'.$path.'" cannot be a directory or link.');

        new TemporaryFile($path, null, 'php-logo.png', 10289, Type::Png);
    }

    public function testConstructorRequiresBaseToBeAbsolutePath(): void
    {
        $base = basename(__DIR__);
        $this->assertFalse(Path::isAbsolute($base));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The base directory "'.$base.'" must be an absolute path.');

        new TemporaryFile(__DIR__.'/../../config/files/php-logo.png', $base, 'php-logo.png', 10289, Type::Png);
    }

    public function testConstructorRequiresPathToBeChildOfBase(): void
    {
        $path = realpath(__DIR__.'/../../config/files/php-logo.png');

        $this->assertIsString($path);
        $this->assertFileExists($path);

        $root = dirname($path);
        $base = sys_get_temp_dir();

        $this->assertDirectoryExists($base);
        $this->assertNotEquals($root, $base);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The path "'.$path.'" must be a direct child of the base directory "'.$base.'".');

        new TemporaryFile($path, $base, 'php-logo.png', 10289, Type::Png);
    }

    public function testConstructorRequiresNonEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The name cannot be empty.');

        new TemporaryFile(__DIR__.'/../../config/files/php-logo.png', null, '', 10289, Type::Png);
    }

    public function testConstructorRequiresNonNegativeSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The size cannot be negative.');

        new TemporaryFile(__DIR__.'/../../config/files/php-logo.png', null, 'php-logo.png', -1, Type::Png);
    }

    public function testConstructorRequiresHashToBeGenerated(): void
    {
        $path = __DIR__.'/files/missing.pdf';
        $this->assertFileDoesNotExist($path);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Generating a hash of the file "'.$path.'" failed.');

        new TemporaryFile($path, null, 'missing.pdf', 10391, Type::Pdf);
    }

    public function testConstructorGeneratesKeyWithoutBaseWhenBaseIsEmpty(): void
    {
        $file = new TemporaryFile(__DIR__.'/../../config/files/php-logo.png', null, 'php-logo.png', 10289, Type::Png)->detach();

        $this->assertNull($file->getBase());
        $this->assertNotEmpty($file->getKey());
    }

    public function testConstructorGeneratesKeyWithBaseWhenBaseIsNotNull(): void
    {
    }

    public function testDestructorDeletesTemporaryFile(): void
    {
        $file = $this->decodeFile('php-logo.png');

        $this->assertIsString($file->getBase());
        $this->assertFileExists($file->getPath());
        $this->assertDirectoryExists($file->getBase());

        $file->__destruct();

        $this->assertIsString($file->getBase());
        $this->assertFileDoesNotExist($file->getPath());
        $this->assertDirectoryDoesNotExist($file->getBase());
    }

    public function testDestructorDoesNotDeleteTemporaryFileWhenFileDoesNotExist(): void
    {
        $file = $this->decodeFile('php-logo.png');

        $this->assertIsString($file->getBase());
        $this->assertFileExists($file->getPath());
        $this->assertDirectoryExists($file->getBase());

        new Filesystem()->remove($file->getPath());
        $this->assertFileDoesNotExist($file->getPath());

        $file->__destruct();

        $this->assertIsString($file->getBase());
        $this->assertFileDoesNotExist($file->getPath());
        $this->assertDirectoryDoesNotExist($file->getBase());
    }

    public function testToStringReturnsPath(): void
    {
        $file = $this->decodeFile('php-logo.png');

        $this->assertEquals($file->getPath(), $file->__toString());
    }

    public function testIsNotEqualWhenFilesHaveDifferentHashes(): void
    {
        $file1 = $this->decodeFile('github-links.pdf');
        $file2 = $this->decodeFile('github-links.docx');

        $this->assertFalse($file1->isEqual($file2));
        $this->assertFalse($file2->isEqual($file1));
        $this->assertNotEquals($file1->getHash(), $file2->getHash());
    }

    public function testIsEqualWhenFilesHaveIdenticalHashes(): void
    {
        $file1 = $this->decodeFile('php-logo.png');
        $file2 = $this->decodeFile('php-logo.png');

        $this->assertTrue($file1->isEqual($file2));
        $this->assertTrue($file2->isEqual($file1));
        $this->assertSame($file1->getHash(), $file2->getHash());
    }

    public function testIsSameWhenFilesHaveIdenticalHashesAndPaths(): void
    {
        $file1 = $this->decodeFile('php-logo.png');
        $file2 = clone $file1;

        $this->assertTrue($file1->isSame($file2));
        $this->assertTrue($file2->isSame($file1));
        $this->assertSame($file1->getPath(), $file2->getPath());
        $this->assertSame($file1->getHash(), $file2->getHash());
    }

    public function testReadingFileRequiresFileToExist(): void
    {
        $file = $this->decodeFile('php-logo.png');
        $this->assertFileExists($file->getPath());

        new Filesystem()->remove($file->getPath());
        $this->assertFileDoesNotExist($file->getPath());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Reading the file "'.$file->getPath().'" failed.');

        $file->read();
    }

    public function testToBase64RequiresFileToExist(): void
    {
        $file = $this->decodeFile('php-logo.png');
        $this->assertFileExists($file->getPath());

        new Filesystem()->remove($file->getPath());
        $this->assertFileDoesNotExist($file->getPath());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Encoding the file "'.$file->getPath().'" as a base64 formatted string failed.');

        $file->toBase64();
    }

    public function testToBase64(): void
    {
        $file = $this->decodeFile('php-logo.png');
        $this->assertFileExists($file->getPath());

        $encodedFile = base64_encode($file->read());
        $this->assertSame($file->toBase64(), $encodedFile);
    }

    public function testToDataUriRequiresFileToExist(): void
    {
        $file = $this->decodeFile('php-logo.png');
        $this->assertFileExists($file->getPath());

        new Filesystem()->remove($file->getPath());
        $this->assertFileDoesNotExist($file->getPath());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Encoding the file "'.$file->getPath().'" as a data URI failed.');

        $file->toDataUri();
    }

    /**
     * @param non-empty-string $name
     */
    private function decodeFile(string $name): TemporaryFile
    {
        /** @var TemporaryFile&TemporaryFileInterface $file */
        $file = new DataDecoder()->decode(__DIR__.'/../../config/files/'.$name);

        return $file;
    }
}
