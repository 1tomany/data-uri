<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\MediaType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MediaTypeTest extends TestCase
{
    public function testKnownTypesExposeTheirPresetMetadata(): void
    {
        $mediaType = MediaType::create(Type::Markdown);

        self::assertSame('text/markdown', $mediaType->value);
        self::assertSame('text/markdown', $mediaType->baseType);
        self::assertSame(Type::Markdown, $mediaType->type);
        self::assertSame('md', $mediaType->getExtension());
        self::assertTrue($mediaType->isText());
    }

    public function testCustomTypesAndParametersArePreserved(): void
    {
        $mediaType = MediaType::fromString('Application/Vnd.Acme+Json; Charset=UTF-8; version=2');

        self::assertSame('application/vnd.acme+json;charset=UTF-8;version=2', (string) $mediaType);
        self::assertSame('application/vnd.acme+json', $mediaType->baseType);
        self::assertSame(Type::Other, $mediaType->type);
        self::assertNull($mediaType->getExtension());
        self::assertTrue($mediaType->isText());
    }

    #[DataProvider('provideInvalidMediaTypes')]
    public function testInvalidTypesAreRejected(string $format): void
    {
        $this->expectException(InvalidArgumentException::class);

        MediaType::fromString($format);
    }

    /**
     * @return list<array{string}>
     */
    public static function provideInvalidMediaTypes(): array
    {
        return [
            [''],
            ['not-a-mime-type'],
            ['text/plain;'],
            ['text/plain;charset'],
            ['text/plain;char set=UTF-8'],
            ["text/plain;charset=UTF-8\nX-Header: value"],
        ];
    }

    public function testTypePresetMatchingIgnoresParameters(): void
    {
        self::assertSame(Type::Txt, Type::createFromFormat('text/plain; charset=UTF-8'));
    }
}
