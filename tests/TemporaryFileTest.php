<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\DataDecoder;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use OneToMany\DataUri\Record\DataUri;
use OneToMany\DataUri\TemporaryFile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function basename;
use function dirname;
use function fclose;
use function gc_collect_cycles;
use function random_bytes;
use function sys_get_temp_dir;

final class TemporaryFileTest extends TestCase
{
    private Filesystem $filesystem;

    private string $temporaryDirectory;

    private DataDecoder $decoder;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->temporaryDirectory = Path::join(sys_get_temp_dir(), 'data-uri-tests-'.bin2hex(random_bytes(8)));
        $this->filesystem->mkdir($this->temporaryDirectory);
        $this->decoder = new DataDecoder(temporaryDirectory: $this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->temporaryDirectory);
    }

    public function testDestructorDeletesOnlyTheOwnedFileAndWorkspace(): void
    {
        $file = $this->decodeFile();
        $path = $file->getPath();
        $workspace = dirname($path);

        self::assertFileExists($path);
        self::assertDirectoryExists($workspace);

        unset($file);
        gc_collect_cycles();

        self::assertFileDoesNotExist($path);
        self::assertDirectoryDoesNotExist($workspace);
    }

    public function testDeleteIsDeterministicAndIdempotent(): void
    {
        $file = $this->decodeFile();
        $path = $file->getPath();

        $file->delete();
        $file->delete();

        self::assertFalse($file->isManaged());
        self::assertFileDoesNotExist($path);
    }

    public function testDetachTransfersCleanupResponsibility(): void
    {
        $file = $this->decodeFile();
        $path = $file->detach();
        $workspace = dirname($path);

        unset($file);
        gc_collect_cycles();

        self::assertFileExists($path);
        self::assertDirectoryExists($workspace);
    }

    public function testReservedNamesCannotEscapeTheirUniqueWorkspace(): void
    {
        $first = $this->decoder->decodeText('first');
        $second = $this->decoder->decodeText('second', name: '..');
        $firstPath = $first->getPath();
        $secondWorkspace = dirname($second->getPath());

        self::assertNotSame(dirname($firstPath), $secondWorkspace);
        self::assertNotSame($this->temporaryDirectory, $secondWorkspace);

        $second->delete();

        self::assertFileExists($firstPath);
        self::assertDirectoryDoesNotExist($secondWorkspace);
    }

    public function testCleanupNeverRecursivelyDeletesUnexpectedFiles(): void
    {
        $file = $this->decodeFile();
        $unexpectedPath = Path::join(dirname($file->getPath()), 'unexpected.txt');
        $this->filesystem->dumpFile($unexpectedPath, 'keep me');

        try {
            $file->delete();
            self::fail('Expected cleanup to reject a non-empty workspace.');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('not empty or is not removable', $e->getMessage());
        }

        self::assertFileExists($unexpectedPath);
        self::assertFileDoesNotExist($file->getPath());
    }

    public function testCleanupRefusesToDeleteADirectoryPlacedAtTheOwnedFilePath(): void
    {
        $file = $this->decodeFile();
        $path = $file->getPath();
        $this->filesystem->remove($path);
        $this->filesystem->mkdir($path);
        $unexpectedPath = Path::join($path, 'unexpected.txt');
        $this->filesystem->dumpFile($unexpectedPath, 'keep me');

        try {
            $file->delete();
            self::fail('Expected cleanup to reject a directory at the file path.');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('Refusing to recursively delete', $e->getMessage());
        }

        self::assertFileExists($unexpectedPath);
    }

    public function testManuallyConstructedFilesAreUnmanaged(): void
    {
        $path = Path::join($this->temporaryDirectory, 'external.txt');
        $this->filesystem->dumpFile($path, 'external');
        $file = new TemporaryFile($path, basename($path), 8, Type::Txt);

        self::assertFalse($file->isManaged());
        unset($file);
        gc_collect_cycles();

        self::assertFileExists($path);
    }

    public function testManagedFilesMustBeDirectChildrenOfTheirOwnedDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a direct child');

        new TemporaryFile(
            Path::join($this->temporaryDirectory, 'child', 'file.txt'),
            'file.txt',
            0,
            Type::Txt,
            ownedDirectory: $this->temporaryDirectory,
        );
    }

    public function testLegacyDataUriConstructionIsNonDestructive(): void
    {
        $path = Path::join($this->temporaryDirectory, 'legacy.txt');
        $this->filesystem->dumpFile($path, 'legacy');
        $file = new DataUri($path, $this->temporaryDirectory, 'legacy.txt', 6, Type::Txt);

        self::assertFalse($file->isManaged());
        unset($file);
        gc_collect_cycles();

        self::assertFileExists($path);
    }

    public function testStringConversionAndStreamAccessReturnThePathAndContents(): void
    {
        $file = $this->decoder->decodeText('Hello');
        $stream = $file->open();

        try {
            self::assertSame($file->getPath(), (string) $file);
            self::assertSame('Hello', stream_get_contents($stream));
        } finally {
            fclose($stream);
        }
    }

    public function testHashAndContentEqualityTrackTheCurrentFileContents(): void
    {
        $first = $this->decodeFile('pdf-small.pdf');
        $second = $this->decodeFile('pdf-small.pdf');
        $different = $this->decodeFile('png-small.png');

        self::assertTrue($first->hasSameContentAs($second));
        self::assertFalse($first->refersToSameFileAs($second));
        self::assertFalse($first->hasSameContentAs($different));
        self::assertTrue($first->refersToSameFileAs($first));
        self::assertTrue($first->equals($second));
        self::assertFalse($first->equals($second, true));
    }

    public function testEncodingHelpers(): void
    {
        $file = $this->decoder->decodeText('Hello', Type::Txt, 'hello.txt');

        self::assertSame('SGVsbG8=', $file->toBase64());
        self::assertSame('data:text/plain;base64,SGVsbG8=', $file->toDataUri());
    }

    public function testOperationsFailAfterDeletion(): void
    {
        $file = $this->decodeFile();
        $file->delete();

        self::assertFalse($file->exists());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Reading the file');

        $file->read();
    }

    private function decodeFile(string $path = 'pdf-small.pdf'): TemporaryFile
    {
        $file = $this->decoder->decode(__DIR__.'/.data/'.$path);

        self::assertInstanceOf(TemporaryFile::class, $file);

        return $file;
    }
}
