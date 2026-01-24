<?php

namespace OneToMany\DataUri\Contract\Enum;

use function in_array;
use function str_replace;
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
    case Webp;
    case Xml;
    case Other;

    public static function create(?string $format): self
    {
        // Clean up the format a bit
        $format = str_replace('.', '', strtolower(trim($format ?? '')));

        if (empty($format)) {
            return self::Other;
        }

        $type = match ($format) {
            // Binaries
            'bin' => self::Bin,
            'dms' => self::Bin,
            'lrf' => self::Bin,
            'mar' => self::Bin,
            'so' => self::Bin,
            'dist' => self::Bin,
            'distz' => self::Bin,
            'pkg' => self::Bin,
            'bpk' => self::Bin,
            'dump' => self::Bin,
            'elc' => self::Bin,
            'deploy' => self::Bin,
            'application/octet-stream' => self::Bin,

            // Bitmaps
            'bmp' => self::Bmp,
            'image/bmp' => self::Bmp,

            // Cascading Style Sheets
            'css' => self::Css,
            'text/css' => self::Css,

            // CSVs
            'csv' => self::Csv,
            'text/csv' => self::Csv,

            // Microsoft Word
            'doc' => self::Doc,
            'application/msword' => self::Doc,
            'docx' => self::Docx,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => self::Docx,

            // GIF Images
            'gif' => self::Gif,
            'image/gif' => self::Gif,

            // HEIC Images
            'heic' => self::Heic,
            'image/heic' => self::Heic,

            // HTML Pages
            'html' => self::Html,
            'text/html' => self::Html,

            // JPEG Images
            'jpeg' => self::Jpeg,
            'jpg' => self::Jpeg,
            'image/jpeg' => self::Jpeg,

            // JSON Documents
            'json' => self::Json,
            'application/json' => self::Json,

            // JSONL Documents
            'jsonl' => self::JsonLines,
            'application/jsonl' => self::JsonLines,

            // PDF Documents
            'pdf' => self::Pdf,
            'application/pdf' => self::Pdf,

            // PNG Images
            'png' => self::Png,
            'image/png' => self::Png,

            // Plain Text Files
            'text' => self::Text,
            'txt' => self::Text,
            'application/x-empty' => self::Txt,
            'text/plain' => self::Txt,

            // TIFF Images
            'tif' => self::Tiff,
            'tiff' => self::Tiff,
            'image/tiff' => self::Tiff,

            // WEBP Images
            'webp' => self::Webp,
            'image/webp' => self::Webp,

            // XML Documents
            'xml' => self::Xml,
            'xsl' => self::Xml,
            'application/xml' => self::Xml,

            // Other Files
            default => self::Other,
        };

        return $type;
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        if ($this->isJsonLines()) {
            return 'JSONL';
        }

        if ($this->isOther()) {
            return $this->name;
        }

        $name = $this->name;

        if ($this->isJpg()) {
            $name = self::Jpeg->name;
        }

        if ($this->isTif()) {
            $name = self::Tiff->name;
        }

        if ($this->isTxt()) {
            $name = self::Text->name;
        }

        return strtoupper($name);
    }

    /**
     * @return ?non-empty-lowercase-string
     */
    public function getExtension(): ?string
    {
        if ($this->isOther()) {
            return null;
        }

        if ($this->isJsonLines()) {
            return 'jsonl';
        }

        if ($this->isText()) {
            return 'txt';
        }

        return strtolower($this->name);
    }

    /**
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
     * @phpstan-assert-if-true self::Bmp|self::Gif|self::Heic|self::Jpg|self::Jpeg|self::Png|self::Tif|self::Tiff|self::Webp $this
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
            self::Webp,
        ]);
    }

    /**
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
     * @phpstan-assert-if-true self::Txt $this
     */
    public function isTxt(): bool
    {
        return self::Txt === $this;
    }

    /**
     * @phpstan-assert-if-true self::Webp $this
     */
    public function isWebp(): bool
    {
        return self::Webp === $this;
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
