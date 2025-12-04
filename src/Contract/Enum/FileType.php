<?php

namespace OneToMany\DataUri\Contract\Enum;

use function in_array;
use function ltrim;
use function strtolower;
use function strtoupper;
use function trim;

enum FileType
{
    case Bin;
    case Bmp;
    case Css;
    case Csv;
    case Doc;
    case Docx;
    case Gif;
    case Heic;
    case Html;
    case Jpeg;
    case Jpg;
    case Json;
    case JsonLines;
    case Pdf;
    case Png;
    case Text;
    case Tif;
    case Tiff;
    case Txt;
    case Xml;
    case Other;

    public static function fromExtension(?string $extension): self
    {
        $extension = trim($extension ?? '');

        if (empty($extension)) {
            return self::Other;
        }

        $extension = strtolower(
            ltrim($extension, '.')
        );

        $type = match ($extension) {
            'bin' => self::Bin,
            'bmp' => self::Bmp,
            'css' => self::Css,
            'csv' => self::Csv,
            'doc' => self::Doc,
            'docx' => self::Docx,
            'gif' => self::Gif,
            'heic' => self::Heic,
            'html' => self::Html,
            'jpeg' => self::Jpeg,
            'jpg' => self::Jpeg,
            'json' => self::Json,
            'jsonl' => self::JsonLines,
            'pdf' => self::Pdf,
            'png' => self::Png,
            'text' => self::Text,
            'tif' => self::Tiff,
            'tiff' => self::Tiff,
            'txt' => self::Text,
            'xml' => self::Xml,
            default => self::Other,
        };

        return $type;
    }

    public function getName(): string
    {
        if ($this->isJsonLines()) {
            return 'JSON Lines';
        }

        if ($this->isText()) {
            return self::Text->name;
        }

        if ($this->isOther()) {
            return $this->name;
        }

        $name = match ($this) {
            self::Jpg => self::Jpeg->name,
            self::Tif => self::Tiff->name,
            default => $this->name,
        };

        return strtoupper($name);
    }

    /**
     * Returns true if the file is not plaintext, false otherwise.
     *
     * @phpstan-assert-if-true self::Bin|self::Bmp|self::Doc|self::Docx|self::Gif|self::Heic|self::Jpg|self::Jpeg|self::Pdf|self::Png|self::Tif|self::Tiff $this
     */
    public function isBinary(): bool
    {
        return in_array($this, [
            self::Bin,
            self::Bmp,
            self::Doc,
            self::Docx,
            self::Gif,
            self::Heic,
            self::Jpeg,
            self::Jpg,
            self::Pdf,
            self::Png,
            self::Tif,
            self::Tiff,
        ]);
    }

    /**
     * Returns true if the file represents a document, false otherwise.
     *
     * @phpstan-assert-if-true self::Css|self::Csv|self::Doc|self::Docx|self::Html|self::Json|self::JsonLines|self::Pdf|self::Text|self::Txt|self::Xml $this
     */
    public function isDocument(): bool
    {
        return in_array($this, [
            self::Css,
            self::Csv,
            self::Doc,
            self::Docx,
            self::Html,
            self::Json,
            self::JsonLines,
            self::Pdf,
            self::Text,
            self::Txt,
            self::Xml,
        ]);
    }

    /**
     * Returns true if the file represents an image, false otherwise.
     *
     * @phpstan-assert-if-true self::Bmp|self::Gif|self::Heic|self::Jpg|self::Jpeg|self::Png|self::Tif|self::Tiff $this
     */
    public function isImage(): bool
    {
        return in_array($this, [
            self::Bmp,
            self::Gif,
            self::Heic,
            self::Jpeg,
            self::Jpg,
            self::Png,
            self::Tif,
            self::Tiff,
        ]);
    }

    /**
     * Returns true if the file is plaintext, false otherwise.
     *
     * @phpstan-assert-if-true self::Css|self::Csv|self::Html|self::Json|self::JsonLines|self::Text|self::Txt|self::Xml $this
     */
    public function isPlainText(): bool
    {
        return in_array($this, [
            self::Css,
            self::Csv,
            self::Html,
            self::Json,
            self::JsonLines,
            self::Text,
            self::Txt,
            self::Xml,
        ]);
    }

    /**
     * @phpstan-assert-if-true self::Bin $this
     */
    public function isBin(): bool
    {
        return self::Bin === $this;
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
     * @phpstan-assert-if-true self::Heic $this
     */
    public function isHeic(): bool
    {
        return self::Heic === $this;
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
     * @phpstan-assert-if-true self::Jpg $this
     */
    public function isJpg(): bool
    {
        return self::Jpg === $this;
    }

    /**
     * @phpstan-assert-if-true self::Json $this
     */
    public function isJson(): bool
    {
        return self::Json === $this;
    }

    /**
     * @phpstan-assert-if-true self::JsonLines $this
     */
    public function isJsonLines(): bool
    {
        return self::JsonLines === $this;
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
     * @phpstan-assert-if-true self::Tif $this
     */
    public function isTif(): bool
    {
        return self::Tif === $this;
    }

    /**
     * @phpstan-assert-if-true self::Tiff|self::Tif $this
     */
    public function isTiff(): bool
    {
        return in_array($this, [self::Tiff, self::Tif]);
    }

    /**
     * @phpstan-assert-if-true self::Xml $this
     */
    public function isXml(): bool
    {
        return self::Xml === $this;
    }

    /**
     * @phpstan-assert-if-true self::Other $this
     */
    public function isOther(): bool
    {
        return self::Other === $this;
    }
}
