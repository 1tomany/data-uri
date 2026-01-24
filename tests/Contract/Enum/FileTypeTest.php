<?php

namespace OneToMany\DataUri\Tests\Contract\Enum;

use OneToMany\DataUri\Contract\Enum\Type;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('ContractTests')]
#[Group('EnumTests')]
final class FileTypeTest extends TestCase
{
    #[DataProvider('providerFormatAndFileType')]
    public function testCreatingFileType(?string $format, Type $fileType): void
    {
        $this->assertSame($fileType, Type::create($format));
    }

    /**
     * @return list<list<bool|string|Type|null>>
     */
    public static function providerFormatAndFileType(): array
    {
        $provider = [
            [null, Type::Other],
            ['', Type::Other],
            [' ', Type::Other],
            ['-', Type::Other],
            ['_', Type::Other],
            ['bin', Type::Bin],
            ['.bin', Type::Bin],
            ['BIN', Type::Bin],
            ['bmp', Type::Bmp],
            ['css', Type::Css],
            ['csv', Type::Csv],
            ['doc', Type::Doc],
            ['docx', Type::Docx],
            ['gif', Type::Gif],
            ['heic', Type::Heic],
            ['html', Type::Html],
            ['jpg', Type::Jpeg],
            ['jpeg', Type::Jpeg],
            ['json', Type::Json],
            ['jsonl', Type::Jsonl],
            ['pdf', Type::Pdf],
            ['png', Type::Png],
            ['text', Type::Text],
            ['tif', Type::Tiff],
            ['tiff', Type::Tiff],
            ['txt', Type::Text],
            ['webp', Type::Webp],
            ['xml', Type::Xml],
        ];

        return $provider;
    }

    #[DataProvider('providerFileTypeAndName')]
    public function testGettingName(Type $fileType, string $name): void
    {
        $this->assertEquals($name, $fileType->getName());
    }

    /**
     * @return list<list<non-empty-string|Type>>
     */
    public static function providerFileTypeAndName(): array
    {
        $provider = [
            [Type::Bin, 'BIN'],
            [Type::Bmp, 'BMP'],
            [Type::Css, 'CSS'],
            [Type::Csv, 'CSV'],
            [Type::Doc, 'DOC'],
            [Type::Docx, 'DOCX'],
            [Type::Gif, 'GIF'],
            [Type::Heic, 'HEIC'],
            [Type::Html, 'HTML'],
            [Type::Jpeg, 'JPEG'],
            [Type::Jpg, 'JPEG'],
            [Type::Json, 'JSON'],
            [Type::Jsonl, 'JSONL'],
            [Type::Pdf, 'PDF'],
            [Type::Png, 'PNG'],
            [Type::Text, 'TEXT'],
            [Type::Tif, 'TIFF'],
            [Type::Tiff, 'TIFF'],
            [Type::Txt, 'TEXT'],
            [Type::Webp, 'WEBP'],
            [Type::Xml, 'XML'],
            [Type::Other, 'Other'],
        ];

        return $provider;
    }

    #[DataProvider('providerFileTypeAndIsBinary')]
    public function testIsBinary(Type $fileType, bool $isBinary): void
    {
        $this->assertSame($isBinary, $fileType->isBinary());
    }

    /**
     * @return list<list<bool|Type>>
     */
    public static function providerFileTypeAndIsBinary(): array
    {
        $provider = [
            [Type::Bin, true],
            [Type::Bmp, true],
            [Type::Css, false],
            [Type::Csv, false],
            [Type::Doc, true],
            [Type::Docx, true],
            [Type::Gif, true],
            [Type::Heic, true],
            [Type::Html, false],
            [Type::Jpeg, true],
            [Type::Jpg, true],
            [Type::Json, false],
            [Type::Jsonl, false],
            [Type::Pdf, true],
            [Type::Png, true],
            [Type::Text, false],
            [Type::Tif, true],
            [Type::Tiff, true],
            [Type::Txt, false],
            [Type::Webp, false],
            [Type::Xml, false],
            [Type::Other, false],
        ];

        return $provider;
    }

    #[DataProvider('providerFileTypeAndIsDocument')]
    public function testIsDocument(Type $fileType, bool $isDocument): void
    {
        $this->assertSame($isDocument, $fileType->isDocument());
    }

    /**
     * @return list<list<bool|Type>>
     */
    public static function providerFileTypeAndIsDocument(): array
    {
        $provider = [
            [Type::Bin, false],
            [Type::Bmp, false],
            [Type::Css, true],
            [Type::Csv, true],
            [Type::Doc, true],
            [Type::Docx, true],
            [Type::Gif, false],
            [Type::Heic, false],
            [Type::Html, true],
            [Type::Jpeg, false],
            [Type::Jpg, false],
            [Type::Json, true],
            [Type::Jsonl, true],
            [Type::Pdf, true],
            [Type::Png, false],
            [Type::Text, true],
            [Type::Tif, false],
            [Type::Tiff, false],
            [Type::Txt, true],
            [Type::Webp, false],
            [Type::Xml, true],
            [Type::Other, false],
        ];

        return $provider;
    }

    #[DataProvider('providerFileTypeAndIsImage')]
    public function testIsImage(Type $fileType, bool $isImage): void
    {
        $this->assertSame($isImage, $fileType->isImage());
    }

    /**
     * @return list<list<bool|Type>>
     */
    public static function providerFileTypeAndIsImage(): array
    {
        $provider = [
            [Type::Bin, false],
            [Type::Bmp, true],
            [Type::Css, false],
            [Type::Csv, false],
            [Type::Doc, false],
            [Type::Docx, false],
            [Type::Gif, true],
            [Type::Heic, true],
            [Type::Html, false],
            [Type::Jpeg, true],
            [Type::Jpg, true],
            [Type::Json, false],
            [Type::Jsonl, false],
            [Type::Pdf, false],
            [Type::Png, true],
            [Type::Text, false],
            [Type::Tif, true],
            [Type::Tiff, true],
            [Type::Txt, false],
            [Type::Webp, true],
            [Type::Xml, false],
            [Type::Other, false],
        ];

        return $provider;
    }

    #[DataProvider('providerFileTypeAndIsPlainText')]
    public function testIsText(Type $fileType, bool $isPlainText): void
    {
        $this->assertSame($isPlainText, $fileType->isText());
    }

    /**
     * @return list<list<bool|Type>>
     */
    public static function providerFileTypeAndIsPlainText(): array
    {
        $provider = [
            [Type::Bin, false],
            [Type::Bmp, false],
            [Type::Css, true],
            [Type::Csv, true],
            [Type::Doc, false],
            [Type::Docx, false],
            [Type::Gif, false],
            [Type::Heic, false],
            [Type::Html, true],
            [Type::Jpeg, false],
            [Type::Jpg, false],
            [Type::Json, true],
            [Type::Jsonl, true],
            [Type::Pdf, false],
            [Type::Png, false],
            [Type::Text, true],
            [Type::Tif, false],
            [Type::Tiff, false],
            [Type::Txt, true],
            [Type::Webp, false],
            [Type::Xml, true],
            [Type::Other, false],
        ];

        return $provider;
    }

    public function testFileTypeJpgIsJpeg(): void
    {
        $this->assertTrue(Type::Jpg->isJpeg()); // @phpstan-ignore-line
    }

    public function testFileTypeTxtIsText(): void
    {
        $this->assertTrue(Type::Txt->isText()); // @phpstan-ignore-line
    }

    public function testFileTypeTifIsTiff(): void
    {
        $this->assertTrue(Type::Tif->isTiff()); // @phpstan-ignore-line
    }

    public function testFileTypeOtherIsOther(): void
    {
        $this->assertTrue(Type::Other->isOther()); // @phpstan-ignore-line
    }
}
