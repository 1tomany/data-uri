<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\DataDecoder;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function array_rand;
use function random_bytes;
use function sprintf;
use function sys_get_temp_dir;

#[Group('UnitTests')]
final class DataDecoderTest extends TestCase
{
    public function testDecodingDataRequiresStringableData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('The data must be a non-NULL string or implement the "\Stringable" interface.');

        new DataDecoder()->decode(null);
    }

    public function testDecodingDataRequiresNonEmptyData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('The data cannot be empty.');

        new DataDecoder()->decode(' ');
    }

    public function testDecodingDataRequiresDataToNotBeDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('The data cannot be a directory.');

        new DataDecoder()->decode(__DIR__);
    }

    public function testDecodingDataRequiresDataToNotContainNonPrintableBytes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('The data cannot contain non-printable, control, or NULL-terminated characters.');

        new DataDecoder()->decode(random_bytes(1024));
    }

    public function testDecodingDataDataRequiresReadableFileToExist(): void
    {
        // Arrange: Create unreadable virtual file
        $file = vfsStream::newFile('Invoice_1984.pdf');
        $file->chmod(0400)->chown(vfsStream::OWNER_ROOT);

        vfsStream::setup(structure: [$file]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('The file "'.$file->url().'" is not readable.');

        $this->assertFileIsNotReadable($file->url());
        new DataDecoder()->decode($file->url());
    }

    public function testDecodingDataRequiresValidDataUri(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Opening a stream to decode the data failed.');

        new DataDecoder()->decode('data:image/gif;base64,!R0lG**AQ/ABAIAAAAAAA++ACH5BAEAAAAALAA?AEAOw==');
    }

    public function testDecodingDataRequiresWritingDataToTemporaryFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/^Copying (.+) to (.+) failed.$/');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())->method('copy')->willThrowException(new IOException('Error'));

        new DataDecoder($filesystem)->decode(__DIR__.'/../config/files/github-links.pdf');
    }

    public function testDecodingDataCanSetName(): void
    {
        $file = new DataDecoder()->decode('data:text/plain,Hello%2C%20world%21', name: 'Hello_World.txt');

        $this->assertEquals('Hello_World.txt', $file->getName());
    }

    public function testDecodingDataCanOverrideSetType(): void
    {
        $file = new DataDecoder()->decode('data:text/plain,Hello%2C%20world%21', 'text/markdown', 'Hello_World.md');

        $this->assertSame(Type::Markdown, $file->getType());
    }

    public function testDecodingFileWithoutNameUsesFileName(): void
    {
        $name = sprintf('%s.txt', __FUNCTION__);
        $path = Path::join(sys_get_temp_dir(), $name);

        $fs = new Filesystem();
        $fs->dumpFile($path, __FUNCTION__);

        $file = new DataDecoder()->decode($path);
        $this->assertEquals($name, $file->getName());

        $fs->remove($path);
    }

    /**
     * @param non-empty-string $data
     * @param non-negative-int $size
     * @param non-empty-string $format
     */
    #[DataProvider('providerDataAndMetadata')]
    public function testDecodingData(string $data, int $size, string $format): void
    {
        $file = new DataDecoder()->decode($data);

        $this->assertFileExists($file->getPath());
        $this->assertEquals($size, $file->getSize());
        $this->assertEquals($format, $file->getFormat());
    }

    /**
     * @return non-empty-list<array{non-empty-string, non-negative-int, non-empty-lowercase-string}>
     */
    public static function providerDataAndMetadata(): array
    {
        $provider = [
            ['data:,Test', 4, 'text/plain'],
            ['data:text/plain,Test', 4, 'text/plain'],
            ['data:text/plain;charset=US-ASCII,Hello%20world', 11, 'text/plain'],
            ['data:;base64,SGVsbG8sIHdvcmxkIQ==', 13, 'text/plain'],
            ['data:text/plain;base64,SGVsbG8sIHdvcmxkIQ==', 13, 'text/plain'],
            ['data:application/json,%7B%22id%22%3A10%7D', 9, 'application/json'],
            ['data:application/json;base64,eyJpZCI6MTB9', 9, 'application/json'],

            // 1x1 Transparent GIF
            ['data:image/gif;base64,R0lGODdhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==', 43, 'image/gif'],

            // @see https://stackoverflow.com/questions/17279712/what-is-the-smallest-possible-valid-pdf#comment59467299_17280876
            ['data:application/pdf;base64,JVBERi0xLg10cmFpbGVyPDwvUm9vdDw8L1BhZ2VzPDwvS2lkc1s8PC9NZWRpYUJveFswIDAgMyAzXT4+XT4+Pj4+Pg==', 67, 'application/pdf'],
        ];

        return $provider;
    }

    /**
     * @param non-empty-string $data
     * @param non-negative-int $size
     * @param non-empty-string $format
     */
    #[DataProvider('providerFileAndMetadata')]
    public function testDecodingFile(string $data, int $size, string $format): void
    {
        $file = new DataDecoder()->decode($data);

        $this->assertFileExists($file->getPath());
        $this->assertEquals($size, $file->getSize());
        $this->assertEquals($format, $file->getFormat());
    }

    /**
     * @return non-empty-list<array{non-empty-string, non-negative-int, non-empty-lowercase-string}>
     */
    public static function providerFileAndMetadata(): array
    {
        $provider = [
            [__DIR__.'/../config/files/github-links.pdf', 36916, 'application/pdf'],
            [__DIR__.'/../config/files/php-logo.png', 10289, 'image/png'],
            [__DIR__.'/../config/files/sample-email.txt', 86, 'text/plain'],
            [__DIR__.'/../config/files/github-links.docx', 6657, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ];

        return $provider;
    }

    public function testDecodingBase64DataRequiresValidFormat(): void
    {
        $format = 'invalid_mime_type';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Opening a stream to decode the data failed.');

        new DataDecoder()->decodeBase64('SGVsbG8sIHdvcmxkIQ==', $format);
    }

    /**
     * @param non-empty-string $data
     * @param non-negative-int $size
     * @param non-empty-string $format
     */
    #[DataProvider('providerBase64DataAndMetadata')]
    public function testDecodingBase64Data(string $data, int $size, string $format): void
    {
        $file = new DataDecoder()->decodeBase64($data, $format);

        $this->assertFileExists($file->getPath());
        $this->assertEquals($size, $file->getSize());
        $this->assertEquals($format, $file->getFormat());
    }

    /**
     * @return non-empty-list<array{non-empty-string, non-negative-int, non-empty-lowercase-string}>
     */
    public static function providerBase64DataAndMetadata(): array
    {
        $provider = [
            ['eyJpZCI6MTB9', 9, 'application/json'],
            ['SGVsbG8sIHdvcmxkIQ==', 13, 'text/plain'],
            ['R0lGODdhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==', 43, 'image/gif'],
            ['iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVQImWNgAAIAAAUAAWJVMogAAAAASUVORK5CYII=', 68, 'image/png'],
        ];

        return $provider;
    }

    public function testDecodingTextDataRequiresTextType(): void
    {
        $types = Type::cases();

        while (true) {
            $type = $types[array_rand($types, 1)];

            if (!$type->isText()) {
                break;
            }
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('The type "'.$type->getName().'" is not text.');

        new DataDecoder()->decodeText('Hello, world!', $type);
    }

    public function testDecodingTextDataGeneratesNameIfNameIsEmpty(): void
    {
        $this->assertNotEmpty(new DataDecoder()->decodeText('Hello, world!', name: '')->getName());
    }

    public function testDecodingTextDataAppendsTxtExtensionIfNameProvidedWithoutOne(): void
    {
        $file = new DataDecoder()->decodeText('Hello, world!', name: 'example.test');

        $this->assertEquals('example.test.txt', $file->getName());
    }

    public function testDecodingTextData(): void
    {
        $file = new DataDecoder()->decodeText('Hello, world!', name: 'HelloWorld.txt');

        $this->assertFileExists($file->getPath());
        $this->assertEquals('Hello, world!', $file->read());
        $this->assertEquals('HelloWorld.txt', $file->getName());
    }

    public function testDecodingTextDataWithTypeOtherThanTxt(): void
    {
        $types = Type::cases();

        while (true) {
            $type = $types[array_rand($types, 1)];

            if ($type->isTxt()) {
                continue;
            }

            if ($type->isText()) {
                break;
            }
        }

        $file = new DataDecoder()->decodeText('Hello, world!', $type, 'HelloWorld');

        $this->assertFileExists($file->getPath());
        $this->assertNotNull($type->getExtension());
        $this->assertStringEndsWith($type->getExtension(), $file->getName());
    }
}
