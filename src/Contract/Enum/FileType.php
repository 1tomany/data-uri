<?php

namespace OneToMany\DataUri\Contract\Enum;

use function in_array;

enum FileType
{
    // Images
    case Bmp;
    case Gif;
    case Jpg;
    case Jpeg;
    case Png;
    case Tif;
    case Tiff;

    // Documents
    case Css;
    case Csv;
    case Doc;
    case Docx;
    case Html;
    case Pdf;
    case Txt;
    case Text;

    // Other
    case Binary;

    /**
     * @phpstan-assert-if-true self::Bmp|self::Gif|self::Jpg|self::Jpeg|self::Png|self::Tif|self::Tiff $this
     */
    public function isImage(): bool
    {
        return in_array($this, [
            self::Bmp,
            self::Gif,
            self::Jpg,
            self::Jpeg,
            self::Png,
            self::Tif,
            self::Tiff,
        ]);
    }

    /**
     * @phpstan-assert-if-true self::Css|self::Csv|self::Doc|self::Docx|self::Html|self::Pdf|self::Txt|self::Text $this
     */
    public function isDocument(): bool
    {
        return in_array($this, [
            self::Css,
            self::Csv,
            self::Doc,
            self::Docx,
            self::Html,
            self::Pdf,
            self::Txt,
            self::Text,
        ]);
    }
}
