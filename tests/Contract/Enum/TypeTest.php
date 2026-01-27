<?php

namespace OneToMany\DataUri\Tests\Contract\Enum;

use OneToMany\DataUri\Contract\Enum\Type;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('ContractTests')]
#[Group('EnumTests')]
final class TypeTest extends TestCase
{
    #[DataProvider('providerFormatAndType')]
    public function testCreatingFromFormat(?string $format, Type $type): void
    {
        $this->assertSame($type, Type::create($format));
    }

    /**
     * @return list<list<bool|string|Type|null>>
     */
    public static function providerFormatAndType(): array
    {
        $provider = [
            [null, Type::Other],
            ['', Type::Other],
            [' ', Type::Other],
            ['application/octet-stream', Type::Bin],
            ['Application/Octet-Stream', Type::Bin],
            ['APPLICATION/OCTET-STREAM', Type::Bin],
            ['image/bmp', Type::Bmp],
            ['text/css', Type::Css],
            ['text/csv', Type::Csv],
            ['application/msword', Type::Doc],
            ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', Type::Docx],
            ['image/gif', Type::Gif],
            ['image/heic', Type::Heic],
            ['image/heic-sequence', Type::Heics],
            ['image/heif', Type::Heif],
            ['image/heif-sequence', Type::Heifs],
            ['text/html', Type::Html],
            ['image/jpg', Type::Jpeg],
            ['image/jpeg', Type::Jpeg],
            ['application/json', Type::Json],
            ['application/jsonl', Type::Jsonl],
            ['audio/x-m4a', Type::M4a],
            ['audio/mp4', Type::M4a],
            ['video/quicktime', Type::Mov],
            ['audio/mpeg', Type::Mp3],
            ['video/mp4', Type::Mp4],
            ['application/pdf', Type::Pdf],
            ['text/x-php', Type::Php],
            ['image/png', Type::Png],
            ['image/tiff', Type::Tiff],
            ['application/x-empty', Type::Txt],
            ['text/plain', Type::Txt],
            ['image/webp', Type::Webp],
            ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', Type::Xlsx],
            ['application/xml', Type::Xml],
            ['application/zip', Type::Zip],
        ];

        return $provider;
    }

    #[DataProvider('providerTypeAndName')]
    public function testGettingName(Type $type, string $name): void
    {
        $this->assertEquals($name, $type->getName());
    }

    /**
     * @return list<list<non-empty-string|Type>>
     */
    public static function providerTypeAndName(): array
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
            [Type::Heics, 'HEICS'],
            [Type::Heif, 'HEIF'],
            [Type::Heifs, 'HEIFS'],
            [Type::Html, 'HTML'],
            [Type::Jpeg, 'JPEG'],
            [Type::Json, 'JSON'],
            [Type::Jsonl, 'JSONL'],
            [Type::M4a, 'M4A'],
            [Type::Mov, 'MOV'],
            [Type::Mp3, 'MP3'],
            [Type::Mp4, 'MP4'],
            [Type::Pdf, 'PDF'],
            [Type::Php, 'PHP'],
            [Type::Png, 'PNG'],
            [Type::Tiff, 'TIFF'],
            [Type::Txt, 'TXT'],
            [Type::Webp, 'WEBP'],
            [Type::Xlsx, 'XLSX'],
            [Type::Xml, 'XML'],
            [Type::Zip, 'ZIP'],
            [Type::Other, 'Other'],
        ];

        return $provider;
    }

    /**
     * @param ?non-empty-lowercase-string $extension
     */
    #[DataProvider('providerTypeAndExtension')]
    public function testGettingExtension(Type $type, ?string $extension): void
    {
        $this->assertEquals($extension, $type->getExtension());
    }

    /**
     * @return list<list<non-empty-lowercase-string|Type|null>>
     */
    public static function providerTypeAndExtension(): array
    {
        $provider = [
            [Type::Bin, 'bin'],
            [Type::Bmp, 'bmp'],
            [Type::Css, 'css'],
            [Type::Csv, 'csv'],
            [Type::Doc, 'doc'],
            [Type::Docx, 'docx'],
            [Type::Gif, 'gif'],
            [Type::Heic, 'heic'],
            [Type::Heics, 'heics'],
            [Type::Heif, 'heif'],
            [Type::Heifs, 'heifs'],
            [Type::Html, 'html'],
            [Type::Jpeg, 'jpeg'],
            [Type::Json, 'json'],
            [Type::Jsonl, 'jsonl'],
            [Type::M4a, 'm4a'],
            [Type::Mov, 'mov'],
            [Type::Mp3, 'mp3'],
            [Type::Mp4, 'mp4'],
            [Type::Pdf, 'pdf'],
            [Type::Php, 'php'],
            [Type::Png, 'png'],
            [Type::Tiff, 'tiff'],
            [Type::Txt, 'txt'],
            [Type::Webp, 'webp'],
            [Type::Xlsx, 'xlsx'],
            [Type::Xml, 'xml'],
            [Type::Zip, 'zip'],
            [Type::Other, null],
        ];

        return $provider;
    }

    #[DataProvider('providerTypeAndIsBinary')]
    public function testIsBinary(Type $fileType, bool $isBinary): void
    {
        $this->assertSame($isBinary, $fileType->isBinary());
    }

    /**
     * @return list<list<bool|Type>>
     */
    public static function providerTypeAndIsBinary(): array
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
            [Type::Heics, true],
            [Type::Heif, true],
            [Type::Heifs, true],
            [Type::Html, false],
            [Type::Jpeg, true],
            [Type::Json, false],
            [Type::Jsonl, false],
            [Type::M4a, true],
            [Type::Mov, true],
            [Type::Mp3, true],
            [Type::Mp4, true],
            [Type::Pdf, true],
            [Type::Php, false],
            [Type::Png, true],
            [Type::Tiff, true],
            [Type::Txt, false],
            [Type::Webp, true],
            [Type::Xlsx, true],
            [Type::Xml, false],
            [Type::Zip, true],
            [Type::Other, false],
        ];

        return $provider;
    }

    #[DataProvider('providerTypeAndIsDocument')]
    public function testIsDocument(Type $type, bool $isDocument): void
    {
        $this->assertSame($isDocument, $type->isDocument());
    }

    /**
     * @return list<list<bool|Type>>
     */
    public static function providerTypeAndIsDocument(): array
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
            [Type::Heics, false],
            [Type::Heif, false],
            [Type::Heifs, false],
            [Type::Html, true],
            [Type::Jpeg, false],
            [Type::Json, true],
            [Type::Jsonl, true],
            [Type::M4a, false],
            [Type::Mov, false],
            [Type::Mp3, false],
            [Type::Mp4, false],
            [Type::Pdf, true],
            [Type::Php, true],
            [Type::Png, false],
            [Type::Tiff, false],
            [Type::Txt, true],
            [Type::Webp, false],
            [Type::Xlsx, true],
            [Type::Xml, true],
            [Type::Zip, false],
            [Type::Other, false],
        ];

        return $provider;
    }

    #[DataProvider('providerFileAndIsImage')]
    public function testIsImage(Type $type, bool $isImage): void
    {
        $this->assertSame($isImage, $type->isImage());
    }

    /**
     * @return list<list<bool|Type>>
     */
    public static function providerFileAndIsImage(): array
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
            [Type::Heics, true],
            [Type::Heif, true],
            [Type::Heifs, true],
            [Type::Html, false],
            [Type::Jpeg, true],
            [Type::Json, false],
            [Type::Jsonl, false],
            [Type::M4a, false],
            [Type::Mov, false],
            [Type::Mp3, false],
            [Type::Mp4, false],
            [Type::Pdf, false],
            [Type::Php, false],
            [Type::Png, true],
            [Type::Tiff, true],
            [Type::Txt, false],
            [Type::Webp, true],
            [Type::Xlsx, false],
            [Type::Xml, false],
            [Type::Zip, false],
            [Type::Other, false],
        ];

        return $provider;
    }

    #[DataProvider('providerTypeAndIsText')]
    public function testIsText(Type $file, bool $isText): void
    {
        $this->assertSame($isText, $file->isText());
    }

    /**
     * @return list<list<bool|Type>>
     */
    public static function providerTypeAndIsText(): array
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
            [Type::Heics, false],
            [Type::Heif, false],
            [Type::Heifs, false],
            [Type::Html, true],
            [Type::Jpeg, false],
            [Type::Json, true],
            [Type::Jsonl, true],
            [Type::M4a, false],
            [Type::Mov, false],
            [Type::Mp3, false],
            [Type::Mp4, false],
            [Type::Pdf, false],
            [Type::Php, true],
            [Type::Png, false],
            [Type::Tiff, false],
            [Type::Txt, true],
            [Type::Webp, false],
            [Type::Xlsx, false],
            [Type::Xml, true],
            [Type::Zip, false],
            [Type::Other, false],
        ];

        return $provider;
    }

    public function testIsBin(): void
    {
        $this->assertTrue(Type::Bin->isBin());
    }

    public function testIsBmp(): void
    {
        $this->assertTrue(Type::Bmp->isBmp());
    }

    public function testIsCss(): void
    {
        $this->assertTrue(Type::Css->isCss());
    }

    public function testIsCsv(): void
    {
        $this->assertTrue(Type::Csv->isCsv());
    }

    public function testIsDoc(): void
    {
        $this->assertTrue(Type::Doc->isDoc());
    }

    public function testIsDocx(): void
    {
        $this->assertTrue(Type::Docx->isDocx());
    }

    public function testIsGif(): void
    {
        $this->assertTrue(Type::Gif->isGif());
    }

    public function testIsHeic(): void
    {
        $this->assertTrue(Type::Heic->isHeic());
    }

    public function testIsHeics(): void
    {
        $this->assertTrue(Type::Heics->isHeics());
    }

    public function testIsHeif(): void
    {
        $this->assertTrue(Type::Heif->isHeif());
    }

    public function testIsHeifs(): void
    {
        $this->assertTrue(Type::Heifs->isHeifs());
    }

    public function testIsHtml(): void
    {
        $this->assertTrue(Type::Html->isHtml());
    }

    public function testIsJpeg(): void
    {
        $this->assertTrue(Type::Jpeg->isJpeg());
    }

    public function testIsJson(): void
    {
        $this->assertTrue(Type::Json->isJson());
    }

    public function testIsJsonl(): void
    {
        $this->assertTrue(Type::Jsonl->isJsonl());
    }

    public function testIsM4a(): void
    {
        $this->assertTrue(Type::M4a->isM4a());
    }

    public function testIsMov(): void
    {
        $this->assertTrue(Type::Mov->isMov());
    }

    public function testIsMp3(): void
    {
        $this->assertTrue(Type::Mp3->isMp3());
    }

    public function testIsMp4(): void
    {
        $this->assertTrue(Type::Mp4->isMp4());
    }

    public function testIsPdf(): void
    {
        $this->assertTrue(Type::Pdf->isPdf());
    }

    public function testIsPhp(): void
    {
        $this->assertTrue(Type::Php->isPhp());
    }

    public function testIsPng(): void
    {
        $this->assertTrue(Type::Png->isPng());
    }

    public function testIsTiff(): void
    {
        $this->assertTrue(Type::Tiff->isTiff());
    }

    public function testIsTxt(): void
    {
        $this->assertTrue(Type::Txt->isTxt());
    }

    public function testIsWebp(): void
    {
        $this->assertTrue(Type::Webp->isWebp());
    }

    public function testIsXlsx(): void
    {
        $this->assertTrue(Type::Xlsx->isXlsx());
    }

    public function testIsXml(): void
    {
        $this->assertTrue(Type::Xml->isXml());
    }

    public function testIsZip(): void
    {
        $this->assertTrue(Type::Zip->isZip());
    }

    public function testIsOther(): void
    {
        $this->assertTrue(Type::Other->isOther());
    }
}
