<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\MediaTypeResolverInterface;
use OneToMany\DataUri\Exception\FileTooLargeException;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use OneToMany\DataUri\MediaType;
use OneToMany\DataUri\TemporaryFileFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function array_diff;
use function array_values;
use function fileperms;
use function fopen;
use function random_bytes;
use function scandir;
use function symlink;
use function sys_get_temp_dir;

final class TemporaryFileFactoryTest extends TestCase
{
    private Filesystem $filesystem;

    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->temporaryDirectory = Path::join(sys_get_temp_dir(), 'data-uri-factory-tests-'.bin2hex(random_bytes(8)));
        $this->filesystem->mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->temporaryDirectory);
    }

    public function testFactoryCreatesPrivateManagedFiles(): void
    {
        $factory = new TemporaryFileFactory($this->filesystem, temporaryDirectory: $this->temporaryDirectory);
        $file = $factory->createFromString('Hello', Type::Txt, 'hello');

        self::assertTrue($file->isManaged());
        self::assertSame('hello.txt', $file->getName());
        self::assertSame(0600, fileperms($file->getPath()) & 0777);
        self::assertSame(0700, fileperms(dirname($file->getPath())) & 0777);
    }

    public function testFactoryRejectsSymbolicLinkBaseDirectories(): void
    {
        $target = Path::join($this->temporaryDirectory, 'target');
        $base = Path::join($this->temporaryDirectory, TemporaryFileFactory::LIBRARY_DIRECTORY);
        $this->filesystem->mkdir($target);
        self::assertTrue(symlink($target, $base));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must be a real directory');

        new TemporaryFileFactory($this->filesystem, temporaryDirectory: $this->temporaryDirectory);
    }

    public function testFactoryStreamsDataWithAnEnforcedLimit(): void
    {
        $factory = new TemporaryFileFactory($this->filesystem, temporaryDirectory: $this->temporaryDirectory, maximumBytes: 4);
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);
        fwrite($stream, 'Hello');
        rewind($stream);

        try {
            $this->expectException(FileTooLargeException::class);
            $factory->createFromStream($stream, Type::Txt);
        } finally {
            fclose($stream);
        }
    }

    public function testFactoryRejectsNonStreamInputs(): void
    {
        $factory = new TemporaryFileFactory($this->filesystem, temporaryDirectory: $this->temporaryDirectory);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be an open stream resource');

        new \ReflectionMethod($factory, 'createFromStream')->invoke($factory, null);
    }

    public function testFactoryRollsBackTheWorkspaceWhenMetadataResolutionFails(): void
    {
        $resolver = new class implements MediaTypeResolverInterface {
            public function resolve(
                string $path,
                string|Type|MediaType|null $preferred = null,
                ?string $declared = null,
            ): MediaType {
                throw new RuntimeException('Metadata failure.');
            }
        };
        $factory = new TemporaryFileFactory($this->filesystem, $resolver, $this->temporaryDirectory);

        try {
            $factory->createFromString('Hello');
            self::fail('Expected media type resolution to fail.');
        } catch (RuntimeException $e) {
            self::assertSame('Metadata failure.', $e->getMessage());
        }

        self::assertSame([], $this->directoryEntries($factory->getBaseDirectory()));
    }

    public function testFactoryRollsBackOversizedStreams(): void
    {
        $factory = new TemporaryFileFactory($this->filesystem, temporaryDirectory: $this->temporaryDirectory, maximumBytes: 4);
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);
        fwrite($stream, 'Hello');
        rewind($stream);

        try {
            $factory->createFromStream($stream);
            self::fail('Expected the stream to exceed the limit.');
        } catch (FileTooLargeException) {
        } finally {
            fclose($stream);
        }

        self::assertSame([], $this->directoryEntries($factory->getBaseDirectory()));
    }

    /**
     * @return list<string>
     */
    private function directoryEntries(string $directory): array
    {
        return array_values(array_diff(scandir($directory) ?: [], ['.', '..']));
    }
}
