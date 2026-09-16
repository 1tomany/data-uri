<?php

namespace OneToMany\DataUri\Tests\Serializer;

use OneToMany\DataUri\Contract\Record\TemporaryFileInterface;
use OneToMany\DataUri\Exception\DomainException;
use OneToMany\DataUri\Serializer\TemporaryFileNormalizer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use function basename;
use function get_debug_type;

use const UPLOAD_ERR_PARTIAL;

#[Group('UnitTests')]
#[Group('SerializerTests')]
final class TemporaryFileNormalizerTest extends TestCase
{
    public function testDenormalizingUploadedFileRequiresItToBeValid(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageIs('The file "photo.jpeg" was only partially uploaded.');

        new TemporaryFileNormalizer()->denormalize(new UploadedFile('/path/to/photo.jpeg', 'photo.jpeg', 'image/jpeg', UPLOAD_ERR_PARTIAL, true), TemporaryFileInterface::class);
    }

    public function testDenormalizingFileUsesFilename(): void
    {
        $file = new TemporaryFileNormalizer()->denormalize(new File(__FILE__), TemporaryFileInterface::class);

        $this->assertEquals('TemporaryFileNormalizerTest.php', $file->getName());
    }

    public function testDenormalizingUploadedFileUsesClientOriginalName(): void
    {
        $file = new TemporaryFileNormalizer()->denormalize(new UploadedFile(__FILE__, basename(__FILE__), 'text/x-php', test: true), TemporaryFileInterface::class);

        $this->assertEquals('TemporaryFileNormalizerTest.php', $file->getName());
    }

    public function testDenormalizingNonSymfonyFileRequiresStringOrStringableData(): void
    {
        $data = new \DateTimeImmutable();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageIs('Expected data of type "string", "'.get_debug_type($data).'" given.');

        new TemporaryFileNormalizer()->denormalize($data, TemporaryFileInterface::class); // @phpstan-ignore argument.type
    }

    public function testDenormalizingStringableNonSymfonyFileObject(): void
    {
        $data = new class implements \Stringable {
            public function __toString(): string
            {
                return 'data:text/plain;base64,SGVsbG8sIHdvcmxkIQ==';
            }
        };

        $file = new TemporaryFileNormalizer()->denormalize($data, TemporaryFileInterface::class);

        $this->assertTrue($file->getType()->isTxt());
        $this->assertEquals('Hello, world!', $file->read());
    }

    public function testDenormalizingText(): void
    {
        $file = new TemporaryFileNormalizer()->denormalize('Hello, world!', TemporaryFileInterface::class);

        $this->assertTrue($file->getType()->isTxt());
        $this->assertEquals('Hello, world!', $file->read());
    }

    public function testDenormalizingDataUri(): void
    {
        $file = new TemporaryFileNormalizer()->denormalize('data:text/plain;base64,SGVsbG8sIHdvcmxkIQ==', TemporaryFileInterface::class);

        $this->assertTrue($file->getType()->isTxt());
        $this->assertEquals('Hello, world!', $file->read());
    }

    public function testDoesNotSupportNormalizationWithNonStringAndNonSymfonyFileDataAndTemporaryFileInterface(): void
    {
        $this->assertFalse(new TemporaryFileNormalizer()->supportsDenormalization(new \stdClass(), TemporaryFileInterface::class));
    }

    public function testSupportsNormalizationWithStringDataAndTemporaryFileInterfaceType(): void
    {
        $this->assertTrue(new TemporaryFileNormalizer()->supportsDenormalization('Hello, world!', TemporaryFileInterface::class));
    }

    public function testSupportsNormalizationWithSymfonyFileDataAndTemporaryFileInterfaceType(): void
    {
        $this->assertTrue(new TemporaryFileNormalizer()->supportsDenormalization(new File('file.pdf', false), TemporaryFileInterface::class));
    }

    public function testDoesNotSupportNormalizationWithEmptyListDataAndTemporaryFileInterfaceType(): void
    {
        $this->assertFalse(new TemporaryFileNormalizer()->supportsDenormalization([], TemporaryFileInterface::class));
    }

    public function testDoesNotSupportNormalizationWithNonEmptyListOfNonStringAndNonSymfonyFileDataAndTemporaryFileInterfaceType(): void
    {
        $this->assertFalse(new TemporaryFileNormalizer()->supportsDenormalization(['Hello, world!', null, new \stdClass()], TemporaryFileInterface::class));
    }

    public function testSupportsNormalizationWithNonEmptyListOfStringsDataAndTemporaryFileInterfaceType(): void
    {
        $this->assertTrue(new TemporaryFileNormalizer()->supportsDenormalization(['Hello, world!'], TemporaryFileInterface::class));
    }

    public function testSupportsNormalizationWithNonEmptyListOfSymfonyFileDataAndTemporaryFileInterfaceType(): void
    {
        $this->assertTrue(new TemporaryFileNormalizer()->supportsDenormalization([new File('file.pdf', false)], TemporaryFileInterface::class));
    }

    public function testSupportsNormalizationWithNonEmptyListOfStringAndSymfonyFileDataAndTemporaryFileInterfaceType(): void
    {
        $data = [
            'Hello, world!',
            new File('file.pdf', false),
            'data:text/plain;base64,SGVsbG8sIHdvcmxkIQ==',
        ];

        $this->assertTrue(new TemporaryFileNormalizer()->supportsDenormalization($data, TemporaryFileInterface::class));
    }
}
