<?php

namespace OneToMany\DataUri\Tests\Source;

use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Source\AllowAnyUrlPolicy;
use OneToMany\DataUri\Source\SourceResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function random_bytes;
use function stream_get_contents;
use function sys_get_temp_dir;

final class SourceResolverTest extends TestCase
{
    private Filesystem $filesystem;

    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->temporaryDirectory = Path::join(sys_get_temp_dir(), 'data-uri-source-tests-'.bin2hex(random_bytes(8)));
        $this->filesystem->mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->temporaryDirectory);
    }

    public function testResolvingDataUrisPreservesDeclaredMetadata(): void
    {
        $source = new SourceResolver()->resolve('data:text/plain;charset=UTF-8,Hello');
        $opened = $source->open();

        try {
            self::assertNull($source->suggestedName);
            self::assertSame('text/plain;charset=UTF-8', $opened->declaredMediaType);
            self::assertSame('Hello', stream_get_contents($opened->getStream()));
        } finally {
            $opened->close();
        }
    }

    public function testResolvingUnicodeLocalFiles(): void
    {
        $path = Path::join($this->temporaryDirectory, 'Résumé.txt');
        $this->filesystem->dumpFile($path, 'Hello');
        $source = new SourceResolver()->resolve($path);
        $opened = $source->open();

        try {
            self::assertSame('Résumé.txt', $source->suggestedName);
            self::assertSame('Hello', stream_get_contents($opened->getStream()));
        } finally {
            $opened->close();
        }
    }

    public function testResolvingFileUrls(): void
    {
        $path = Path::join($this->temporaryDirectory, 'hello.txt');
        $this->filesystem->dumpFile($path, 'Hello');
        $source = new SourceResolver()->resolve('file://'.$path);
        $opened = $source->open();

        try {
            self::assertSame('Hello', stream_get_contents($opened->getStream()));
        } finally {
            $opened->close();
        }
    }

    public function testDefaultPolicyRejectsLocalhost(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not public');

        new SourceResolver()->resolve('https://localhost/file.txt');
    }

    public function testUrlPolicyCanBeReplacedExplicitly(): void
    {
        $source = new SourceResolver(new AllowAnyUrlPolicy())->resolve('https://localhost/my%20file.txt');

        self::assertSame('my file.txt', $source->suggestedName);
    }
}
