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
    #[DataProvider('providerFileTypeAndIsDocument')]
    public function testIsDocument(FileType $fileType, bool $isDocument): void
    {
        $this->assertSame($fileType->isDocument(), $isDocument);
    }

    #[DataProvider('providerFileTypeAndIsImage')]
    public function testIsImage(FileType $fileType, bool $isImage): void
    {
        $this->assertSame($fileType->isImage(), $isImage);
    }

    /**
     * @return list<list<bool|FileType>>
     */
    public static function providerFileTypeAndIsDocument(): array
    {
        $provider = [
            [FileType::Binary, false],
            [FileType::Bmp, false],
            [FileType::Css, true],
            [FileType::Csv, true],
            [FileType::Doc, true],
            [FileType::Docx, true],
            [FileType::Gif, false],
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

    /**
     * @return list<list<bool|FileType>>
     */
    public static function providerFileTypeAndIsImage(): array
    {
        $provider = [
            [FileType::Binary, false],
            [FileType::Bmp, true],
            [FileType::Css, false],
            [FileType::Csv, false],
            [FileType::Doc, false],
            [FileType::Docx, false],
            [FileType::Gif, true],
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
}
