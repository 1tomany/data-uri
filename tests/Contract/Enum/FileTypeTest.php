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
    #[DataProvider('providerTypeAndFileType')]
    public function testCreatingFromType(?string $type, FileType $fileType): void
    {
        $this->assertSame($fileType, FileType::createFromType($type));
    }

    /**
     * @return non-empty-list<array{string|null, FileType}>
     */
    public static function providerTypeAndFileType(): array
    {
        $provider = [
            [null, FileType::Other],
            ['', FileType::Other],
            [' ', FileType::Other],
            ['audio/aac', FileType::Aac],
            ['audio/aiff', FileType::Aiff],
            ['application/octet-stream', FileType::Bin],
            ['Application/Octet-Stream', FileType::Bin],
            ['APPLICATION/OCTET-STREAM', FileType::Bin],
            ['image/bmp', FileType::Bmp],
            ['text/css', FileType::Css],
            ['text/csv', FileType::Csv],
            ['application/msword', FileType::Doc],
            ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', FileType::Docx],
            ['audio/flac', FileType::Flac],
            ['image/gif', FileType::Gif],
            ['image/heic', FileType::Heic],
            ['image/heic-sequence', FileType::Heics],
            ['image/heif', FileType::Heif],
            ['image/heif-sequence', FileType::Heifs],
            ['text/html', FileType::Html],
            ['image/jpg', FileType::Jpg],
            ['image/jpeg', FileType::Jpeg],
            ['text/javascript', FileType::Js],
            ['application/json', FileType::Json],
            ['application/jsonl', FileType::Jsonl],
            ['audio/x-m4a', FileType::M4a],
            ['audio/mp4', FileType::M4a],
            ['text/markdown', FileType::Markdown],
            ['video/quicktime', FileType::Mov],
            ['audio/mpeg', FileType::Mp3],
            ['video/mp4', FileType::Mp4],
            ['audio/ogg', FileType::Oga],
            ['application/pdf', FileType::Pdf],
            ['text/x-php', FileType::Php],
            ['image/png', FileType::Png],
            ['image/tiff', FileType::Tiff],
            ['application/x-empty', FileType::Txt],
            ['text/plain', FileType::Txt],
            ['audio/wav', FileType::Wav],
            ['image/webp', FileType::Webp],
            ['application/msexcel', FileType::Xls],
            ['application/vnd.ms-excel', FileType::Xls],
            ['application/x-excel', FileType::Xls],
            ['application/x-msexcel', FileType::Xls],
            ['application/x-ms-excel', FileType::Xls],
            ['application/xls', FileType::Xls],
            ['application/x-xls', FileType::Xls],
            ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', FileType::Xlsx],
            ['application/xml', FileType::Xml],
            ['application/zip', FileType::Zip],
        ];

        return $provider;
    }

    #[DataProvider('providerPathWithShortExtensionAndFileType')]
    public function testCreatingFromPathWithShortExtension(string $path, FileType $fileType): void
    {
        $this->assertSame($fileType, FileType::createFromPath($path));
    }

    /**
     * @return non-empty-list<array{non-empty-string, FileType}>
     */
    public static function providerPathWithShortExtensionAndFileType(): array
    {
        $provider = [
            ['index.htm', FileType::Htm],
            ['label.jpg', FileType::Jpg],
        ];

        return $provider;
    }

    #[DataProvider('providerFileTypeAndName')]
    public function testGettingName(FileType $fileType, string $name): void
    {
        $this->assertEquals($name, $fileType->getName());
    }

    /**
     * @return non-empty-list<array{FileType, non-empty-string}>
     */
    public static function providerFileTypeAndName(): array
    {
        $provider = [
            [FileType::Aac, 'AAC'],
            [FileType::Aiff, 'AIFF'],
            [FileType::Bin, 'BIN'],
            [FileType::Bmp, 'BMP'],
            [FileType::Css, 'CSS'],
            [FileType::Csv, 'CSV'],
            [FileType::Doc, 'DOC'],
            [FileType::Docx, 'DOCX'],
            [FileType::Flac, 'FLAC'],
            [FileType::Gif, 'GIF'],
            [FileType::Heic, 'HEIC'],
            [FileType::Heics, 'HEICS'],
            [FileType::Heif, 'HEIF'],
            [FileType::Heifs, 'HEIFS'],
            [FileType::Htm, 'HTM'],
            [FileType::Html, 'HTML'],
            [FileType::Jpeg, 'JPEG'],
            [FileType::Jpg, 'JPG'],
            [FileType::Js, 'JS'],
            [FileType::Json, 'JSON'],
            [FileType::Jsonl, 'JSONL'],
            [FileType::M4a, 'M4A'],
            [FileType::Markdown, 'MD'],
            [FileType::Mov, 'MOV'],
            [FileType::Mp3, 'MP3'],
            [FileType::Mp4, 'MP4'],
            [FileType::Oga, 'OGA'],
            [FileType::Pdf, 'PDF'],
            [FileType::Php, 'PHP'],
            [FileType::Png, 'PNG'],
            [FileType::Tiff, 'TIFF'],
            [FileType::Txt, 'TXT'],
            [FileType::Wav, 'WAV'],
            [FileType::Webp, 'WEBP'],
            [FileType::Xls, 'XLS'],
            [FileType::Xlsx, 'XLSX'],
            [FileType::Xml, 'XML'],
            [FileType::Zip, 'ZIP'],
            [FileType::Other, 'Other'],
        ];

        return $provider;
    }

    /**
     * @param ?non-empty-lowercase-string $extension
     */
    #[DataProvider('providerFileTypeAndExtension')]
    public function testGettingExtension(FileType $fileType, ?string $extension): void
    {
        $this->assertEquals($extension, $fileType->getExtension());
    }

    /**
     * @return non-empty-list<array{FileType, non-empty-lowercase-string|null}>
     */
    public static function providerFileTypeAndExtension(): array
    {
        $provider = [
            [FileType::Aac, 'aac'],
            [FileType::Aiff, 'aiff'],
            [FileType::Bin, 'bin'],
            [FileType::Bmp, 'bmp'],
            [FileType::Css, 'css'],
            [FileType::Csv, 'csv'],
            [FileType::Doc, 'doc'],
            [FileType::Docx, 'docx'],
            [FileType::Flac, 'flac'],
            [FileType::Gif, 'gif'],
            [FileType::Heic, 'heic'],
            [FileType::Heics, 'heics'],
            [FileType::Heif, 'heif'],
            [FileType::Heifs, 'heifs'],
            [FileType::Htm, 'htm'],
            [FileType::Html, 'html'],
            [FileType::Jpeg, 'jpeg'],
            [FileType::Jpg, 'jpg'],
            [FileType::Js, 'js'],
            [FileType::Json, 'json'],
            [FileType::Jsonl, 'jsonl'],
            [FileType::M4a, 'm4a'],
            [FileType::Markdown, 'md'],
            [FileType::Mov, 'mov'],
            [FileType::Mp3, 'mp3'],
            [FileType::Mp4, 'mp4'],
            [FileType::Oga, 'oga'],
            [FileType::Pdf, 'pdf'],
            [FileType::Php, 'php'],
            [FileType::Png, 'png'],
            [FileType::Tiff, 'tiff'],
            [FileType::Txt, 'txt'],
            [FileType::Wav, 'wav'],
            [FileType::Webp, 'webp'],
            [FileType::Xls, 'xls'],
            [FileType::Xlsx, 'xlsx'],
            [FileType::Xml, 'xml'],
            [FileType::Zip, 'zip'],
            [FileType::Other, null],
        ];

        return $provider;
    }

    /**
     * @param non-empty-lowercase-string $format
     */
    #[DataProvider('providerFileTypeAndFormat')]
    public function testGettingFormat(FileType $fileType, string $format): void
    {
        $this->assertEquals($format, $fileType->getFormat());
    }

    /**
     * @return non-empty-list<array{FileType, non-empty-lowercase-string}>
     */
    public static function providerFileTypeAndFormat(): array
    {
        $provider = [
            [FileType::Aac, 'audio/aac'],
            [FileType::Aiff, 'audio/aiff'],
            [FileType::Bmp, 'image/bmp'],
            [FileType::Css, 'text/css'],
            [FileType::Csv, 'text/csv'],
            [FileType::Doc, 'application/msword'],
            [FileType::Docx, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            [FileType::Docx, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            [FileType::Flac, 'audio/flac'],
            [FileType::Gif, 'image/gif'],
            [FileType::Heic, 'image/heic'],
            [FileType::Heics, 'image/heic-sequence'],
            [FileType::Heif, 'image/heif'],
            [FileType::Heifs, 'image/heif-sequence'],
            [FileType::Htm, 'text/html'],
            [FileType::Html, 'text/html'],
            [FileType::Jpeg, 'image/jpeg'],
            [FileType::Jpg, 'image/jpeg'],
            [FileType::Js, 'text/javascript'],
            [FileType::Json, 'application/json'],
            [FileType::Jsonl, 'application/jsonl'],
            [FileType::M4a, 'audio/x-m4a'],
            [FileType::Markdown, 'text/markdown'],
            [FileType::Mov, 'video/quicktime'],
            [FileType::Mp3, 'audio/mpeg'],
            [FileType::Mp4, 'video/mp4'],
            [FileType::Oga, 'audio/ogg'],
            [FileType::Pdf, 'application/pdf'],
            [FileType::Php, 'text/x-php'],
            [FileType::Png, 'image/png'],
            [FileType::Txt, 'text/plain'],
            [FileType::Tiff, 'image/tiff'],
            [FileType::Wav, 'audio/wav'],
            [FileType::Webp, 'image/webp'],
            [FileType::Xls, 'application/vnd.ms-excel'],
            [FileType::Xlsx, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            [FileType::Xml, 'application/xml'],
            [FileType::Zip, 'application/zip'],
            [FileType::Other, 'application/octet-stream'],
        ];

        return $provider;
    }

    #[DataProvider('providerTypeAndIsAudio')]
    public function testIsAudio(FileType $fileType, bool $isAudio): void
    {
        $this->assertSame($isAudio, $fileType->isAudio());
    }

    /**
     * @return non-empty-list<array{Type, bool}>
     */
    public static function providerTypeAndIsAudio(): array
    {
        $provider = [
            [FileType::Aac, true],
            [FileType::Aiff, true],
            [FileType::Bin, false],
            [FileType::Bmp, false],
            [FileType::Css, false],
            [FileType::Csv, false],
            [FileType::Doc, false],
            [FileType::Docx, false],
            [FileType::Flac, true],
            [FileType::Gif, false],
            [FileType::Heic, false],
            [FileType::Heics, false],
            [FileType::Heif, false],
            [FileType::Heifs, false],
            [FileType::Htm, false],
            [FileType::Html, false],
            [FileType::Jpeg, false],
            [FileType::Jpg, false],
            [FileType::Js, false],
            [FileType::Json, false],
            [FileType::Jsonl, false],
            [FileType::M4a, true],
            [FileType::Markdown, false],
            [FileType::Mov, false],
            [FileType::Mp3, true],
            [FileType::Mp4, false],
            [FileType::Oga, true],
            [FileType::Pdf, false],
            [FileType::Php, false],
            [FileType::Png, false],
            [FileType::Tiff, false],
            [FileType::Txt, false],
            [FileType::Wav, true],
            [FileType::Webp, false],
            [FileType::Xls, false],
            [FileType::Xlsx, false],
            [FileType::Xml, false],
            [FileType::Zip, false],
            [FileType::Other, false],
        ];

        return $provider;
    }

    #[DataProvider('providerTypeAndIsBinary')]
    public function testIsBinary(FileType $fileType, bool $isBinary): void
    {
        $this->assertSame($isBinary, $fileType->isBinary());
    }

    /**
     * @return non-empty-list<array{Type, bool}>
     */
    public static function providerTypeAndIsBinary(): array
    {
        $provider = [
            [FileType::Aac, true],
            [FileType::Aiff, true],
            [FileType::Bin, true],
            [FileType::Bmp, true],
            [FileType::Css, false],
            [FileType::Csv, false],
            [FileType::Doc, true],
            [FileType::Docx, true],
            [FileType::Flac, true],
            [FileType::Gif, true],
            [FileType::Heic, true],
            [FileType::Heics, true],
            [FileType::Heif, true],
            [FileType::Heifs, true],
            [FileType::Htm, false],
            [FileType::Html, false],
            [FileType::Jpeg, true],
            [FileType::Jpg, true],
            [FileType::Js, false],
            [FileType::Json, false],
            [FileType::Jsonl, false],
            [FileType::M4a, true],
            [FileType::Markdown, false],
            [FileType::Mov, true],
            [FileType::Mp3, true],
            [FileType::Mp4, true],
            [FileType::Oga, true],
            [FileType::Pdf, true],
            [FileType::Php, false],
            [FileType::Png, true],
            [FileType::Tiff, true],
            [FileType::Txt, false],
            [FileType::Wav, true],
            [FileType::Webp, true],
            [FileType::Xls, true],
            [FileType::Xlsx, true],
            [FileType::Xml, false],
            [FileType::Zip, true],
            [FileType::Other, false],
        ];

        return $provider;
    }

    #[DataProvider('providerTypeAndIsDocument')]
    public function testIsDocument(FileType $type, bool $isDocument): void
    {
        $this->assertSame($isDocument, $type->isDocument());
    }

    /**
     * @return non-empty-list<array{Type, bool}>
     */
    public static function providerTypeAndIsDocument(): array
    {
        $provider = [
            [FileType::Aac, false],
            [FileType::Aiff, false],
            [FileType::Bin, false],
            [FileType::Bmp, false],
            [FileType::Css, true],
            [FileType::Csv, true],
            [FileType::Doc, true],
            [FileType::Docx, true],
            [FileType::Flac, false],
            [FileType::Gif, false],
            [FileType::Heic, false],
            [FileType::Heics, false],
            [FileType::Heif, false],
            [FileType::Heifs, false],
            [FileType::Htm, true],
            [FileType::Html, true],
            [FileType::Jpeg, false],
            [FileType::Jpg, false],
            [FileType::Js, true],
            [FileType::Json, true],
            [FileType::Jsonl, true],
            [FileType::M4a, false],
            [FileType::Markdown, true],
            [FileType::Mov, false],
            [FileType::Mp3, false],
            [FileType::Mp4, false],
            [FileType::Oga, false],
            [FileType::Pdf, true],
            [FileType::Php, true],
            [FileType::Png, false],
            [FileType::Tiff, false],
            [FileType::Txt, true],
            [FileType::Wav, false],
            [FileType::Webp, false],
            [FileType::Xls, true],
            [FileType::Xlsx, true],
            [FileType::Xml, true],
            [FileType::Zip, false],
            [FileType::Other, false],
        ];

        return $provider;
    }

    #[DataProvider('providerFileAndIsImage')]
    public function testIsImage(FileType $type, bool $isImage): void
    {
        $this->assertSame($isImage, $type->isImage());
    }

    /**
     * @return non-empty-list<array{Type, bool}>
     */
    public static function providerFileAndIsImage(): array
    {
        $provider = [
            [FileType::Aac, false],
            [FileType::Aiff, false],
            [FileType::Bin, false],
            [FileType::Bmp, true],
            [FileType::Css, false],
            [FileType::Csv, false],
            [FileType::Doc, false],
            [FileType::Docx, false],
            [FileType::Flac, false],
            [FileType::Gif, true],
            [FileType::Heic, true],
            [FileType::Heics, true],
            [FileType::Heif, true],
            [FileType::Heifs, true],
            [FileType::Htm, false],
            [FileType::Html, false],
            [FileType::Jpeg, true],
            [FileType::Jpg, true],
            [FileType::Js, false],
            [FileType::Json, false],
            [FileType::Jsonl, false],
            [FileType::M4a, false],
            [FileType::Markdown, false],
            [FileType::Mov, false],
            [FileType::Mp3, false],
            [FileType::Mp4, false],
            [FileType::Oga, false],
            [FileType::Pdf, false],
            [FileType::Php, false],
            [FileType::Png, true],
            [FileType::Tiff, true],
            [FileType::Txt, false],
            [FileType::Wav, false],
            [FileType::Webp, true],
            [FileType::Xls, false],
            [FileType::Xlsx, false],
            [FileType::Xml, false],
            [FileType::Zip, false],
            [FileType::Other, false],
        ];

        return $provider;
    }

    #[DataProvider('providerTypeAndIsText')]
    public function testIsText(FileType $file, bool $isText): void
    {
        $this->assertSame($isText, $file->isText());
    }

    /**
     * @return non-empty-list<array{Type, bool}>
     */
    public static function providerTypeAndIsText(): array
    {
        $provider = [
            [FileType::Aac, false],
            [FileType::Aiff, false],
            [FileType::Bin, false],
            [FileType::Bmp, false],
            [FileType::Css, true],
            [FileType::Csv, true],
            [FileType::Doc, false],
            [FileType::Docx, false],
            [FileType::Flac, false],
            [FileType::Gif, false],
            [FileType::Heic, false],
            [FileType::Heics, false],
            [FileType::Heif, false],
            [FileType::Heifs, false],
            [FileType::Htm, true],
            [FileType::Html, true],
            [FileType::Jpeg, false],
            [FileType::Jpg, false],
            [FileType::Js, true],
            [FileType::Json, true],
            [FileType::Jsonl, true],
            [FileType::M4a, false],
            [FileType::Markdown, true],
            [FileType::Mov, false],
            [FileType::Mp3, false],
            [FileType::Mp4, false],
            [FileType::Oga, false],
            [FileType::Pdf, false],
            [FileType::Php, true],
            [FileType::Png, false],
            [FileType::Tiff, false],
            [FileType::Txt, true],
            [FileType::Wav, false],
            [FileType::Webp, false],
            [FileType::Xls, false],
            [FileType::Xlsx, false],
            [FileType::Xml, true],
            [FileType::Zip, false],
            [FileType::Other, false],
        ];

        return $provider;
    }

    public function testIsAac(): void
    {
        $this->assertTrue(FileType::Aac->isAac()); // @phpstan-ignore-line
    }

    public function testIsAiff(): void
    {
        $this->assertTrue(FileType::Aiff->isAiff()); // @phpstan-ignore-line
    }

    public function testIsBin(): void
    {
        $this->assertTrue(FileType::Bin->isBin()); // @phpstan-ignore-line
    }

    public function testIsBmp(): void
    {
        $this->assertTrue(FileType::Bmp->isBmp()); // @phpstan-ignore-line
    }

    public function testIsCss(): void
    {
        $this->assertTrue(FileType::Css->isCss()); // @phpstan-ignore-line
    }

    public function testIsCsv(): void
    {
        $this->assertTrue(FileType::Csv->isCsv()); // @phpstan-ignore-line
    }

    public function testIsDoc(): void
    {
        $this->assertTrue(FileType::Doc->isDoc()); // @phpstan-ignore-line
    }

    public function testIsDocx(): void
    {
        $this->assertTrue(FileType::Docx->isDocx()); // @phpstan-ignore-line
    }

    public function testIsFlac(): void
    {
        $this->assertTrue(FileType::Flac->isFlac()); // @phpstan-ignore-line
    }

    public function testIsGif(): void
    {
        $this->assertTrue(FileType::Gif->isGif()); // @phpstan-ignore-line
    }

    public function testIsHeic(): void
    {
        $this->assertTrue(FileType::Heic->isHeic()); // @phpstan-ignore-line
    }

    public function testIsHeics(): void
    {
        $this->assertTrue(FileType::Heics->isHeics()); // @phpstan-ignore-line
    }

    public function testIsHeif(): void
    {
        $this->assertTrue(FileType::Heif->isHeif()); // @phpstan-ignore-line
    }

    public function testIsHeifs(): void
    {
        $this->assertTrue(FileType::Heifs->isHeifs()); // @phpstan-ignore-line
    }

    public function testIsHtm(): void
    {
        $this->assertTrue(FileType::Htm->isHtm()); // @phpstan-ignore-line
    }

    public function testIsHtml(): void
    {
        $this->assertTrue(FileType::Html->isHtml()); // @phpstan-ignore-line
    }

    public function testIsJpeg(): void
    {
        $this->assertTrue(FileType::Jpeg->isJpeg()); // @phpstan-ignore-line
    }

    public function testIsJpg(): void
    {
        $this->assertTrue(FileType::Jpg->isJpg()); // @phpstan-ignore-line
    }

    public function testIsJs(): void
    {
        $this->assertTrue(FileType::Js->isJs()); // @phpstan-ignore-line
    }

    public function testIsJson(): void
    {
        $this->assertTrue(FileType::Json->isJson()); // @phpstan-ignore-line
    }

    public function testIsJsonl(): void
    {
        $this->assertTrue(FileType::Jsonl->isJsonl()); // @phpstan-ignore-line
    }

    public function testIsM4a(): void
    {
        $this->assertTrue(FileType::M4a->isM4a()); // @phpstan-ignore-line
    }

    public function testIsMarkdown(): void
    {
        $this->assertTrue(FileType::Markdown->isMarkdown()); // @phpstan-ignore-line
    }

    public function testIsMov(): void
    {
        $this->assertTrue(FileType::Mov->isMov()); // @phpstan-ignore-line
    }

    public function testIsMp3(): void
    {
        $this->assertTrue(FileType::Mp3->isMp3()); // @phpstan-ignore-line
    }

    public function testIsMp4(): void
    {
        $this->assertTrue(FileType::Mp4->isMp4()); // @phpstan-ignore-line
    }

    public function testIsOga(): void
    {
        $this->assertTrue(FileType::Oga->isOga()); // @phpstan-ignore-line
    }

    public function testIsPdf(): void
    {
        $this->assertTrue(FileType::Pdf->isPdf()); // @phpstan-ignore-line
    }

    public function testIsPhp(): void
    {
        $this->assertTrue(FileType::Php->isPhp()); // @phpstan-ignore-line
    }

    public function testIsPng(): void
    {
        $this->assertTrue(FileType::Png->isPng()); // @phpstan-ignore-line
    }

    public function testIsTiff(): void
    {
        $this->assertTrue(FileType::Tiff->isTiff()); // @phpstan-ignore-line
    }

    public function testIsTxt(): void
    {
        $this->assertTrue(FileType::Txt->isTxt()); // @phpstan-ignore-line
    }

    public function testIsWav(): void
    {
        $this->assertTrue(FileType::Wav->isWav()); // @phpstan-ignore-line
    }

    public function testIsWebp(): void
    {
        $this->assertTrue(FileType::Webp->isWebp()); // @phpstan-ignore-line
    }

    public function testIsXls(): void
    {
        $this->assertTrue(FileType::Xls->isXls()); // @phpstan-ignore-line
    }

    public function testIsXlsx(): void
    {
        $this->assertTrue(FileType::Xlsx->isXlsx()); // @phpstan-ignore-line
    }

    public function testIsXml(): void
    {
        $this->assertTrue(FileType::Xml->isXml()); // @phpstan-ignore-line
    }

    public function testIsZip(): void
    {
        $this->assertTrue(FileType::Zip->isZip()); // @phpstan-ignore-line
    }

    public function testIsOther(): void
    {
        $this->assertTrue(FileType::Other->isOther()); // @phpstan-ignore-line
    }
}
