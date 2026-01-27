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

}
