<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Contract\Exception\ExceptionInterface as DataUriExceptionInterface;
use OneToMany\DataUri\DataDecoder;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

use function assert;
use function base64_encode;
use function basename;
use function random_bytes;
use function sys_get_temp_dir;
use function vsprintf;

#[Group('UnitTests')]
final class DataDecoderTest extends TestCase
{
    // use TestFileTrait;

    public function testDecodingDataRequiresStringableData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data must be a non-NULL string or implement the "\Stringable" interface.');

        new DataDecoder()->decode(null);
    }

    public function testDecodingDataRequiresNonEmptyData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data cannot be empty.');

        new DataDecoder()->decode(' ');
    }

    public function testDecodingDataRequiresDataToNotBeADirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data cannot be a directory.');

        new DataDecoder()->decode(__DIR__);
    }

    public function testDecodingDataRequiresDataToNotContainNonPrintableBytes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data cannot contain non-printable, control, or NULL-terminated characters.');

        new DataDecoder()->decode(random_bytes(1024));
    }

    public function testDecodingDataDataRequiresReadableFileToExist(): void
    {
        // Arrange: Create unreadable virtual file
        $file = vfsStream::newFile('Invoice_1984.pdf');
        $file->chmod(0400)->chown(vfsStream::OWNER_ROOT);

        vfsStream::setup(structure: [$file]);

        // Assert: Virtual file is not readable
        $this->assertFileIsNotReadable($file->url());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The file "'.$file->url().'" is not readable.');

        new DataDecoder()->decode($file->url());
    }

    public function testDecodingDataRequiresValidDataUri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decoding the data using a stream failed.');

        new DataDecoder()->decode('data:image/gif;base64,!R0lG**AQ/ABAIAAAAAAA++ACH5BAEAAAAALAA?AEAOw==');
    }

    public function testDecodingDataRequiresWritingDataToTemporaryFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/^Copying (.+) to (.+) failed.$/');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())->method('copy')->willThrowException(new IOException('Error'));

        new DataDecoder($filesystem)->decode(__DIR__.'/data/pdf-small.pdf');
    }

    public function testDecodingDataCanSetName(): void
    {
        $file = new DataDecoder()->decode('data:text/plain,Hello%2C%20world%21,', 'Hello_World.txt');

        $this->assertEquals('Hello_World.txt', $file->name);
    }
}
