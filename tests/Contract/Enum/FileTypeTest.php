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
}
