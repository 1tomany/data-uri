<?php

namespace OneToMany\DataUri\Tests\Contract\Enum;

use OneToMany\DataUri\Contract\Enum\FileType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('ContractTests')]
#[Group('EnumTests')]
final class FileTypeTest extends TestCase
{
    #[DataProvider('providerExtensionAndFileType')]
    public function testFromExtension(?string $extension, FileType $fileType): void
    {
        $this->assertSame($fileType, FileType::fromExtension($extension));
    }

    /**
     * @return list<list<bool|string|FileType|null>>
     */
    public static function providerExtensionAndFileType(): array
    {
        $provider = [
            [null, FileType::Other],
            ['', FileType::Other],
            [' ', FileType::Other],
            ['-', FileType::Other],
            ['_', FileType::Other],
            ['bin', FileType::Bin],
            ['.bin', FileType::Bin],
            ['BIN', FileType::Bin],
            ['bmp', FileType::Bmp],
            ['css', FileType::Css],
            ['csv', FileType::Csv],
            ['doc', FileType::Doc],
            ['docx', FileType::Docx],
            ['gif', FileType::Gif],
            ['heic', FileType::Heic],
            ['html', FileType::Html],
            ['jpg', FileType::Jpeg],
            ['jpeg', FileType::Jpeg],
            ['json', FileType::Json],
            ['jsonl', FileType::JsonLines],
            ['pdf', FileType::Pdf],
            ['png', FileType::Png],
            ['text', FileType::Text],
            ['tif', FileType::Tiff],
            ['tiff', FileType::Tiff],
            ['txt', FileType::Text],
            ['xml', FileType::Xml],
        ];

        return $provider;
    }

    #[DataProvider('providerFileTypeAndName')]
    public function testGettingName(FileType $fileType, string $name): void
    {
        $this->assertEquals($name, $fileType->getName());
    }

    /**
     * @return list<list<non-empty-string|FileType>>
     */
    public static function providerFileTypeAndName(): array
    {
        $provider = [
            [FileType::Bin, 'BIN'],
            [FileType::Bmp, 'BMP'],
            [FileType::Css, 'CSS'],
            [FileType::Csv, 'CSV'],
            [FileType::Doc, 'DOC'],
            [FileType::Docx, 'DOCX'],
            [FileType::Gif, 'GIF'],
            [FileType::Heic, 'HEIC'],
            [FileType::Html, 'HTML'],
            [FileType::Jpeg, 'JPEG'],
            [FileType::Jpg, 'JPEG'],
            [FileType::Json, 'JSON'],
            [FileType::JsonLines, 'JSON Lines'],
            [FileType::Pdf, 'PDF'],
            [FileType::Png, 'PNG'],
            [FileType::Text, 'Text'],
            [FileType::Tif, 'TIFF'],
            [FileType::Tiff, 'TIFF'],
            [FileType::Txt, 'Text'],
            [FileType::Xml, 'XML'],
            [FileType::Other, 'Other'],
        ];

        return $provider;
    }

    #[DataProvider('providerFileTypeAndIsBinary')]
    public function testIsBinary(FileType $fileType, bool $isBinary): void
    {
        $this->assertSame($isBinary, $fileType->isBinary());
    }

    /**
     * @return list<list<bool|FileType>>
     */
    public static function providerFileTypeAndIsBinary(): array
    {
        $provider = [
            [FileType::Bin, true],
            [FileType::Bmp, true],
            [FileType::Css, false],
            [FileType::Csv, false],
            [FileType::Doc, true],
            [FileType::Docx, true],
            [FileType::Gif, true],
            [FileType::Heic, true],
            [FileType::Html, false],
            [FileType::Jpeg, true],
            [FileType::Jpg, true],
            [FileType::Json, false],
            [FileType::JsonLines, false],
            [FileType::Pdf, true],
            [FileType::Png, true],
            [FileType::Text, false],
            [FileType::Tif, true],
            [FileType::Tiff, true],
            [FileType::Txt, false],
            [FileType::Xml, false],
            [FileType::Other, false],
        ];

        return $provider;
    }

    #[DataProvider('providerFileTypeAndIsDocument')]
    public function testIsDocument(FileType $fileType, bool $isDocument): void
    {
        $this->assertSame($isDocument, $fileType->isDocument());
    }

    /**
     * @return list<list<bool|FileType>>
     */
    public static function providerFileTypeAndIsDocument(): array
    {
        $provider = [
            [FileType::Bin, false],
            [FileType::Bmp, false],
            [FileType::Css, true],
            [FileType::Csv, true],
            [FileType::Doc, true],
            [FileType::Docx, true],
            [FileType::Gif, false],
            [FileType::Heic, false],
            [FileType::Html, true],
            [FileType::Jpeg, false],
            [FileType::Jpg, false],
            [FileType::Json, true],
            [FileType::JsonLines, true],
            [FileType::Pdf, true],
            [FileType::Png, false],
            [FileType::Text, true],
            [FileType::Tif, false],
            [FileType::Tiff, false],
            [FileType::Txt, true],
            [FileType::Xml, true],
            [FileType::Other, false],
        ];

        return $provider;
    }

    #[DataProvider('providerFileTypeAndIsImage')]
    public function testIsImage(FileType $fileType, bool $isImage): void
    {
        $this->assertSame($isImage, $fileType->isImage());
    }

    /**
     * @return list<list<bool|FileType>>
     */
    public static function providerFileTypeAndIsImage(): array
    {
        $provider = [
            [FileType::Bin, false],
            [FileType::Bmp, true],
            [FileType::Css, false],
            [FileType::Csv, false],
            [FileType::Doc, false],
            [FileType::Docx, false],
            [FileType::Gif, true],
            [FileType::Heic, true],
            [FileType::Html, false],
            [FileType::Jpeg, true],
            [FileType::Jpg, true],
            [FileType::Json, false],
            [FileType::JsonLines, false],
            [FileType::Pdf, false],
            [FileType::Png, true],
            [FileType::Text, false],
            [FileType::Tif, true],
            [FileType::Tiff, true],
            [FileType::Txt, false],
            [FileType::Xml, false],
            [FileType::Other, false],
        ];

        return $provider;
    }

    #[DataProvider('providerFileTypeAndIsPlainText')]
    public function testIsPlainText(FileType $fileType, bool $isPlainText): void
    {
        $this->assertSame($isPlainText, $fileType->isPlainText());
    }

    /**
     * @return list<list<bool|FileType>>
     */
    public static function providerFileTypeAndIsPlainText(): array
    {
        $provider = [
            [FileType::Bin, false],
            [FileType::Bmp, false],
            [FileType::Css, true],
            [FileType::Csv, true],
            [FileType::Doc, false],
            [FileType::Docx, false],
            [FileType::Gif, false],
            [FileType::Heic, false],
            [FileType::Html, true],
            [FileType::Jpeg, false],
            [FileType::Jpg, false],
            [FileType::Json, true],
            [FileType::JsonLines, true],
            [FileType::Pdf, false],
            [FileType::Png, false],
            [FileType::Text, true],
            [FileType::Tif, false],
            [FileType::Tiff, false],
            [FileType::Txt, true],
            [FileType::Xml, true],
            [FileType::Other, false],
        ];

        return $provider;
    }

    public function testFileTypeJpgIsJpeg(): void
    {
        $this->assertTrue(FileType::Jpg->isJpeg()); // @phpstan-ignore-line
    }

    public function testFileTypeTxtIsText(): void
    {
        $this->assertTrue(FileType::Txt->isText()); // @phpstan-ignore-line
    }

    public function testFileTypeTifIsTiff(): void
    {
        $this->assertTrue(FileType::Tif->isTiff()); // @phpstan-ignore-line
    }

    public function testFileTypeOtherIsOther(): void
    {
        $this->assertTrue(FileType::Other->isOther()); // @phpstan-ignore-line
    }
}
