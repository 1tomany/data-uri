<?php

namespace OneToMany\DataUri\Contract\Enum;

use function in_array;
use function ltrim;
use function strtolower;
use function trim;

enum FileType
{
    // Documents
    case Css;
    case Csv;
    case Doc;
    case Docx;
    case Html;
    case Pdf;
    case Text;
    case Txt;

    // Images
    case Bmp;
    case Gif;
    case Jpeg;
    case Jpg;
    case Png;
    case Tif;
    case Tiff;

    // Other
    case Binary;

    public static function fromExtension(?string $extension): ?self
    {
        // Clean up the extension by removing
        // any leading periods and whitespace
        $extension = trim($extension ?? '');
        $extension = ltrim($extension, '.');
        $extension = strtolower($extension);

        $fileType = match ($extension) {
            'bin' => self::Binary,
            'bmp' => self::Bmp,
            'css' => self::Css,
            'csv' => self::Csv,
            'doc' => self::Doc,
            'docx' => self::Docx,
            'gif' => self::Gif,
            'html' => self::Html,
            'jpg' => self::Jpeg,
            'jpeg' => self::Jpeg,
            'pdf' => self::Pdf,
            'png' => self::Png,
            'text' => self::Text,
            'tif' => self::Tiff,
            'tiff' => self::Tiff,
            'txt' => self::Text,
            default => null,
        };

        return $fileType;
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

    /**
     * @phpstan-assert-if-true self::Bmp|self::Gif|self::Jpg|self::Jpeg|self::Png|self::Tif|self::Tiff $this
     */
    public function isImage(): bool
    {
        return in_array($this, [
            self::Bmp,
            self::Gif,
            self::Jpeg,
            self::Jpg,
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

    /**
     * @phpstan-assert-if-true self::Bmp $this
     */
    public function isBmp(): bool
    {
        return self::Bmp === $this;
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
     * @phpstan-assert-if-true self::Gif $this
     */
    public function isGif(): bool
    {
        return self::Gif === $this;
    }

    /**
     * @phpstan-assert-if-true self::Html $this
     */
    public function isHtml(): bool
    {
        return self::Html === $this;
    }

    /**
     * @phpstan-assert-if-true self::Jpeg|self::Jpg $this
     */
    public function isJpeg(): bool
    {
        return in_array($this, [self::Jpeg, self::Jpg]);
    }

    /**
     * @phpstan-assert-if-true self::Pdf $this
     */
    public function isPdf(): bool
    {
        return self::Pdf === $this;
    }

    /**
     * @phpstan-assert-if-true self::Png $this
     */
    public function isPng(): bool
    {
        return self::Png === $this;
    }

    /**
     * @phpstan-assert-if-true self::Text|self::Txt $this
     */
    public function isText(): bool
    {
        return in_array($this, [self::Text, self::Txt]);
    }

    /**
     * @phpstan-assert-if-true self::Tiff|self::Tif $this
     */
    public function isTiff(): bool
    {
        return in_array($this, [self::Tiff, self::Tif]);
    }
}
