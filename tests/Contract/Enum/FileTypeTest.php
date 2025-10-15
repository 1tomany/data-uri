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
        $this->assertSame(FileType::fromExtension($extension), $fileType);
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
            ['pdf', FileType::Pdf],
            ['png', FileType::Png],
            ['text', FileType::Text],
            ['tif', FileType::Tiff],
            ['tiff', FileType::Tiff],
            ['txt', FileType::Text],
        ];

        return $provider;
    }

    #[DataProvider('providerFileTypeAndIsDocument')]
    public function testIsDocument(FileType $fileType, bool $isDocument): void
    {
        $this->assertSame($fileType->isDocument(), $isDocument);
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
            [FileType::Pdf, true],
            [FileType::Png, false],
            [FileType::Text, true],
            [FileType::Txt, true],
            [FileType::Tif, false],
            [FileType::Tiff, false],
        ];

        return $provider;
    }

    #[DataProvider('providerFileTypeAndIsImage')]
    public function testIsImage(FileType $fileType, bool $isImage): void
    {
        $this->assertSame($fileType->isImage(), $isImage);
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
            [FileType::Pdf, false],
            [FileType::Png, true],
            [FileType::Text, false],
            [FileType::Txt, false],
            [FileType::Tif, true],
            [FileType::Tiff, true],
        ];

        return $provider;
    }

    public function testFileTypeJpgIsJpeg(): void
    {
        $this->assertTrue(FileType::Jpg->isJpeg()); // @phpstan-ignore-line
    }

    public function testFileTypeOtherIsOther(): void
    {
        $this->assertTrue(FileType::Other->isOther()); // @phpstan-ignore-line
    }

    public function testFileTypeTxtIsText(): void
    {
        $this->assertTrue(FileType::Txt->isText()); // @phpstan-ignore-line
    }

    public function testFileTypeTifIsTiff(): void
    {
        $this->assertTrue(FileType::Tif->isTiff()); // @phpstan-ignore-line
    }
}
