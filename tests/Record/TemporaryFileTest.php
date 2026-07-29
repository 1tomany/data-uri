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

use function basename;
use function is_dir;
use function is_file;
use function sys_get_temp_dir;

final class TemporaryFileTest extends TestCase
{
    public function testConstructorRequiresNonEmptyPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The path cannot be empty.');

        new TemporaryFile('', null, 'png-small.png', 10289, Type::Png);
    }

    public function testConstructorRequiresPathToNotBeDirectory(): void
    {
        $path = \dirname(__DIR__.'/../.data/png-small.png');

        $this->assertTrue(is_dir($path));
        $this->assertFalse(is_file($path));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The path "'.$path.'" cannot be a directory or link.');

        new TemporaryFile($path, null, 'png-small.png', 10289, Type::Png);
    }

    public function testConstructorRequiresRootToBeAbsolutePath(): void
    {
        $root = basename(__DIR__);
        $this->assertFalse(Path::isAbsolute($root));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The root directory "'.$root.'" must be a non-empty absolute path.');

        new TemporaryFile(__DIR__.'/../.data/png-small.png', $root, 'png-small.png', 10289, Type::Png);
    }

    public function testConstructorRequiresPathToBeChildOfRoot(): void
    {
        $path = __DIR__.'/../.data/png-small.png';
        $this->assertTrue(Path::isAbsolute($path));

        $root = sys_get_temp_dir();
        $this->assertNotEquals(Path::getDirectory($path), $root);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The path "'.$path.'" must be a direct child of the root "'.$root.'".');

        new TemporaryFile($path, $root, 'png-small.png', 10289, Type::Png);
    }

    public function testConstructorRequiresNonEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The name cannot be empty.');

        new TemporaryFile(__DIR__.'/../.data/png-small.png', null, '', 10289, Type::Png);
    }

    public function testConstructorRequiresNonNegativeSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The size cannot be negative.');

        new TemporaryFile(__DIR__.'/../.data/png-small.png', null, 'png-small.png', -1, Type::Png);
    }

    public function testConstructorRequiresHashToBeGenerated(): void
    {
        $path = __DIR__.'/files/missing.pdf';
        $this->assertFileDoesNotExist($path);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Generating a hash of the file "'.$path.'" failed.');

        new TemporaryFile($path, null, 'missing.pdf', 10391, Type::Pdf);
    }

    public function testDestructorDeletesTemporaryFile(): void
    {
        $file = $this->decodeFile();

        $this->assertIsString($file->getRoot());
        $this->assertFileExists($file->getPath());
        $this->assertDirectoryExists($file->getRoot());

        $file->__destruct();

        $this->assertIsString($file->getRoot());
        $this->assertFileDoesNotExist($file->getPath());
        $this->assertDirectoryDoesNotExist($file->getRoot());
    }

    public function testDestructorDoesNotDeleteTemporaryFileWhenFileDoesNotExist(): void
    {
        $file = $this->decodeFile();
        $this->assertFileExists($file->getPath());

        new Filesystem()->remove($file->getPath());
        $this->assertFileDoesNotExist($file->getPath());

        $file->__destruct();

        $this->assertFileDoesNotExist($file->getPath());
        $this->assertDirectoryDoesNotExist(dirname($file->getPath()));
    }

    /**
     * @param non-empty-string $name
     */
    private function decodeFile(string $name = 'pdf-small.pdf'): TemporaryFile
    {
        /** @var TemporaryFile&TemporaryFileInterface $file */
        $file = new DataDecoder()->decode(__DIR__.'/../.data/'.$name);

        return $file;
    }
}
