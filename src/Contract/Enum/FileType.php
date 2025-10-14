<?php

namespace OneToMany\DataUri\Contract\Enum;

use function in_array;

enum FileType
{
    // Documents
    case Css;
    case Csv;
    case Doc;
    case Docx;
    case Html;
    case Pdf;
    case Txt;
    case Text;

    // Images
    case Bmp;
    case Gif;
    case Jpg;
    case Jpeg;
    case Png;
    case Tif;
    case Tiff;

    // Other
    case Binary;

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

    /**
     * @phpstan-assert-if-true self::Css $this
     */
    public function isCss(): bool
    {
        return self::Css === $this;
    }

    /**
     * @phpstan-assert-if-true self::Csv $this
     */
    public function isCsv(): bool
    {
        return self::Csv === $this;
    }

    /**
     * @phpstan-assert-if-true self::Doc $this
     */
    public function isDoc(): bool
    {
        return self::Doc === $this;
    }

    /**
     * @phpstan-assert-if-true self::Docx $this
     */
    public function isDocx(): bool
    {
        return self::Docx === $this;
    }

    /**
     * @phpstan-assert-if-true self::Html $this
     */
    public function isHtml(): bool
    {
        return self::Html === $this;
    }

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
     * @phpstan-assert-if-true self::Binary $this
     */
    public function isBinary(): bool
    {
        return self::Binary === $this;
    }
}
