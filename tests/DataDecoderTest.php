<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\DataDecoder;
use OneToMany\DataUri\Exception\FileTooLargeException;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\MediaType;
use OneToMany\DataUri\TemporaryFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
final class DataDecoderTest extends TestCase
{
    public function testDecodeRequiresStringableData(): void
    {
        $this->expectException(\TypeError::class);

        new \ReflectionMethod(DataDecoder::class, 'decode')->invoke(new DataDecoder(), null);
    }

    public function testDecodeRejectsEmptyData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data cannot be empty.');

        new DataDecoder()->decode(' ');
    }

    public function testDecodeRejectsDirectories(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data cannot be a directory.');

        new DataDecoder()->decode(__DIR__);
    }

    public function testDecodeRejectsControlCharacters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data cannot contain control or NULL characters.');

        new DataDecoder()->decode("data:text/plain,hello\0world");
    }

    public function testDecodeRejectsMissingFiles(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist or is not readable');

        new DataDecoder()->decode(__DIR__.'/missing.txt');
    }

    public function testDecodeRejectsMalformedDataUris(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not contain a data separator');

        new DataDecoder()->decode('data:text/plain');
    }

    public function testDecodeRejectsUnsupportedStreamSchemes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The stream scheme "ftp" is not supported.');

        new DataDecoder()->decode('ftp://example.com/file.txt');
    }

    public function testDecodeRejectsPrivateRemoteAddressesBeforeOpeningThem(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('private or reserved');

        new DataDecoder()->decode('http://127.0.0.1/file.txt');
    }

    public function testDecodeAcceptsStringableData(): void
    {
        $data = new class implements \Stringable {
            public function __toString(): string
            {
                return 'data:text/plain,Hello';
            }
        };

        $file = new DataDecoder()->decode($data);

        self::assertSame('Hello', $file->read());
    }

    public function testDecodeUsesAndSanitizesTheProvidedName(): void
    {
        $file = new DataDecoder()->decode('data:text/plain,Hello', name: '../Résumé 2026.txt');

        self::assertSame('Résumé_2026.txt', $file->getName());
        self::assertSame('../Résumé 2026.txt', $file->getOriginalName());
    }

    public function testDecodePreservesCustomParameterizedMediaTypes(): void
    {
        $file = new DataDecoder()->decode(
            'data:text/plain,Hello',
            'application/vnd.acme+json; charset=UTF-8',
            'payload',
        );

        self::assertSame(Type::Other, $file->getType());
        self::assertSame('application/vnd.acme+json;charset=UTF-8', $file->getFormat());
        self::assertSame('payload', $file->getName());
    }

    public function testDecodeUsesTheDeclaredDataUriMediaTypeBeforeSniffing(): void
    {
        $file = new DataDecoder()->decode('data:text/markdown;charset=UTF-8,%23%20Hello');

        self::assertSame(Type::Markdown, $file->getType());
        self::assertSame('text/markdown;charset=UTF-8', $file->getFormat());
        self::assertSame('# Hello', $file->read());
    }

    public function testDecodeUsesTheOriginalFileName(): void
    {
        $file = new DataDecoder()->decode(__DIR__.'/.data/text-small.txt');

        self::assertSame('text-small.txt', $file->getName());
        self::assertSame('text-small.txt', $file->getOriginalName());
    }

    /**
     * @param non-empty-string $data
     * @param non-negative-int $size
     * @param non-empty-string $format
     */
    #[DataProvider('provideDataAndMetadata')]
    public function testDecodeData(string $data, int $size, string $format): void
    {
        $file = new DataDecoder()->decode($data);

        self::assertInstanceOf(TemporaryFile::class, $file);
        self::assertFileExists($file->getPath());
        self::assertSame($size, $file->getSize());
        self::assertSame($format, $file->getFormat());
    }

    /**
     * @return list<array{non-empty-string, non-negative-int, non-empty-string}>
     */
    public static function provideDataAndMetadata(): array
    {
        return [
            ['data:,Test', 4, 'text/plain'],
            ['data:text/plain,Test', 4, 'text/plain'],
            ['data:text/plain;charset=US-ASCII,Hello%20world', 11, 'text/plain;charset=US-ASCII'],
            ['data:;base64,SGVsbG8sIHdvcmxkIQ==', 13, 'text/plain'],
            ['data:application/json,%7B%22id%22%3A10%7D', 9, 'application/json'],
            ['data:image/gif;base64,R0lGODdhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==', 43, 'image/gif'],
        ];
    }

    /**
     * @param non-empty-string $path
     * @param non-negative-int $size
     * @param non-empty-string $format
     */
    #[DataProvider('provideFilesAndMetadata')]
    public function testDecodeFiles(string $path, int $size, string $format): void
    {
        $file = new DataDecoder()->decode($path);

        self::assertFileExists($file->getPath());
        self::assertSame($size, $file->getSize());
        self::assertSame($format, $file->getFormat());
    }

    /**
     * @return list<array{non-empty-string, non-negative-int, non-empty-string}>
     */
    public static function provideFilesAndMetadata(): array
    {
        return [
            [__DIR__.'/.data/pdf-small.pdf', 36916, Type::Pdf->getFormat()],
            [__DIR__.'/.data/png-small.png', 10289, Type::Png->getFormat()],
            [__DIR__.'/.data/text-small.txt', 86, Type::Txt->getFormat()],
            [__DIR__.'/.data/word-small.docx', 6657, Type::Docx->getFormat()],
        ];
    }

    public function testDecodeEnforcesTheConfiguredMaximumSize(): void
    {
        $this->expectException(FileTooLargeException::class);
        $this->expectExceptionMessage('maximum size of 4 bytes');

        new DataDecoder(maximumBytes: 4)->decode('data:text/plain,Hello');
    }

    public function testDecodeStreamReadsFromTheCurrentPositionWithoutClosingTheInput(): void
    {
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);
        fwrite($stream, 'skipHello');
        fseek($stream, 4);

        try {
            $file = new DataDecoder()->decodeStream($stream, Type::Txt, 'hello');

            self::assertSame('Hello', $file->read());
            self::assertSame('stream', get_resource_type($stream));
        } finally {
            fclose($stream);
        }
    }

    public function testDecodeBase64RejectsInvalidMediaTypes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The format "invalid_mime_type" is invalid.');

        new DataDecoder()->decodeBase64('SGVsbG8=', 'invalid_mime_type');
    }

    public function testDecodeBase64RejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data is not valid Base64.');

        new DataDecoder()->decodeBase64('not*base64', Type::Txt);
    }

    public function testDecodeBase64RejectsContentThatCannotFitBeforeDecodingIt(): void
    {
        $this->expectException(FileTooLargeException::class);
        $this->expectExceptionMessage('may exceed the maximum size of 3 bytes');

        new DataDecoder(maximumBytes: 3)->decodeBase64('SGVsbG8=', Type::Txt);
    }

    /**
     * @param non-empty-string $data
     * @param non-negative-int $size
     */
    #[DataProvider('provideBase64Data')]
    public function testDecodeBase64(string $data, int $size, Type $type): void
    {
        $file = new DataDecoder()->decodeBase64($data, $type);

        self::assertSame($size, $file->getSize());
        self::assertSame($type, $file->getType());
    }

    /**
     * @return list<array{non-empty-string, non-negative-int, Type}>
     */
    public static function provideBase64Data(): array
    {
        return [
            ['eyJpZCI6MTB9', 9, Type::Json],
            ['SGVsbG8sIHdvcmxkIQ==', 13, Type::Txt],
            ['R0lGODdhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==', 43, Type::Gif],
        ];
    }

    public function testDecodeTextRejectsBinaryTypesDeterministically(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The media type "image/png" is not text.');

        new DataDecoder()->decodeText('Hello', Type::Png);
    }

    public function testDecodeTextAcceptsCustomTextTypes(): void
    {
        $type = MediaType::fromString('text/x-prompt; charset=UTF-8');
        $file = new DataDecoder()->decodeText('Hello, LLM!', $type, 'prompt');

        self::assertSame('Hello, LLM!', $file->read());
        self::assertSame('text/x-prompt;charset=UTF-8', $file->getFormat());
        self::assertSame('prompt', $file->getName());
    }

    public function testDecodeTextAppendsTheKnownExtension(): void
    {
        $file = new DataDecoder()->decodeText('Hello', Type::Markdown, 'example.test');

        self::assertSame('example.test.md', $file->getName());
    }
}
