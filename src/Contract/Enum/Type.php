<?php

namespace OneToMany\DataUri\Contract\Enum;

use function in_array;
use function mime_content_type;
use function strtolower;
use function strtoupper;

enum Type
{
    case Bin;
    case Bmp;
    case Css;
    case Csv;
    case Doc;
    case Docx;
    case Gif;
    case Heic;
    case Heics;
    case Heif;
    case Heifs;
    case Html;
    case Jpeg;
    case Json;
    case Jsonl;
    case Pdf;
    case Php;
    case Png;
    case Tiff;
    case Txt;
    case Webp;
    case Xlsx;
    case Xml;
    case Zip;
    case Other;

    public static function createFromPath(string $path): self
    {
        $format = @mime_content_type($path);

        $type = match ($format) {
            'application/octet-stream' => self::Bin,
            'image/bmp' => self::Bmp,
            'text/css' => self::Css,
            'text/csv' => self::Csv,
            'application/msword' => self::Doc,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => self::Docx,
            'image/gif' => self::Gif,
            'image/heic' => self::Heic,
            'image/heic-sequence' => self::Heics,
            'image/heif' => self::Heif,
            'image/heif-sequence' => self::Heifs,
            'text/html' => self::Html,
            'image/jpeg' => self::Jpeg,
            'application/json' => self::Json,
            'application/jsonl' => self::Jsonl,
            'application/pdf' => self::Pdf,
            'text/x-php' => self::Php,
            'image/png' => self::Png,
            'application/x-empty' => self::Txt,
            'text/plain' => self::Txt,
            'image/tiff' => self::Tiff,
            'image/webp' => self::Webp,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => self::Xlsx,
            'application/xml' => self::Xml,
            'application/zip' => self::Zip,
            default => null,
        };

        return $type ?? self::Other;
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        if ($this->isOther()) {
            return $this->name;
        }

        return strtoupper($this->name);
    }

    /**
     * @return ?non-empty-lowercase-string
     */
    public function getExtension(): ?string
    {
        if ($this->isOther()) {
            return null;
        }

        return strtolower($this->name);
    }

    /**
     * @return non-empty-lowercase-string
     */
    public function getFormat(): string
    {
        $format = match ($this) {
            self::Bmp => 'image/bmp',
            self::Css => 'text/css',
            self::Csv => 'text/csv',
            self::Doc => 'application/msword',
            self::Docx => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            self::Gif => 'image/gif',
            self::Heic => 'image/heic',
            self::Heics => 'image/heic-sequence',
            self::Heif => 'image/heif',
            self::Heifs => 'image/heif-sequence',
            self::Html => 'text/html',
            self::Jpeg => 'image/jpeg',
            self::Json => 'application/json',
            self::Jsonl => 'application/jsonl',
            self::Pdf => 'application/pdf',
            self::Php => 'text/x-php',
            self::Png => 'image/png',
            self::Txt => 'text/plain',
            self::Tiff => 'image/tiff',
            self::Webp => 'image/webp',
            self::Xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::Xml => 'application/xml',
            self::Zip => 'application/zip',
            default => 'application/octet-stream',
        };

        return $format;
    }

    /**
     * @phpstan-assert-if-true self::Bin|self::Bmp|self::Doc|self::Docx|self::Gif|self::Heic|self::Heics|self::Heif|self::Heifs|self::Jpeg|self::Pdf|self::Png|self::Tiff|self::Webp|self::Xlsx|self::Zip $this
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
            self::Heics,
            self::Heif,
            self::Heifs,
            self::Jpeg,
            self::Pdf,
            self::Png,
            self::Tiff,
            self::Webp,
            self::Xlsx,
            self::Zip,
        ]);
    }

    /**
     * @phpstan-assert-if-true self::Css|self::Csv|self::Doc|self::Docx|self::Html|self::Json|self::Jsonl|self::Php|self::Pdf|self::Txt|self::Xml $this
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
            self::Jsonl,
            self::Pdf,
            self::Php,
            self::Txt,
            self::Xml,
        ]);
    }

    /**
     * @phpstan-assert-if-true self::Bmp|self::Gif|self::Heic|self::Heics|self::Heif|self::Heifs|self::Jpeg|self::Png|self::Tiff|self::Webp $this
     */
    public function isImage(): bool
    {
        return in_array($this, [
            self::Bmp,
            self::Gif,
            self::Heic,
            self::Heics,
            self::Heif,
            self::Heifs,
            self::Jpeg,
            self::Png,
            self::Tiff,
            self::Webp,
        ]);
    }

    /**
     * @phpstan-assert-if-true self::Css|self::Csv|self::Html|self::Json|self::Jsonl|self::Php|self::Txt|self::Xml $this
     */
    public function isText(): bool
    {
        return in_array($this, [
            self::Css,
            self::Csv,
            self::Html,
            self::Json,
            self::Jsonl,
            self::Php,
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
     * @phpstan-assert-if-true self::Heics $this
     */
    public function isHeics(): bool
    {
        return self::Heics === $this;
    }

    /**
     * @phpstan-assert-if-true self::Heif $this
     */
    public function isHeif(): bool
    {
        return self::Heif === $this;
    }

    /**
     * @phpstan-assert-if-true self::Heifs $this
     */
    public function isHeifs(): bool
    {
        return self::Heifs === $this;
    }

    /**
     * @phpstan-assert-if-true self::Html $this
     */
    public function isHtml(): bool
    {
        return self::Html === $this;
    }

    /**
     * @phpstan-assert-if-true self::Jpeg $this
     */
    public function isJpeg(): bool
    {
        return self::Jpeg === $this;
    }

    /**
     * @phpstan-assert-if-true self::Json $this
     */
    public function isJson(): bool
    {
        return self::Json === $this;
    }

    /**
     * @phpstan-assert-if-true self::Jsonl $this
     */
    public function isJsonl(): bool
    {
        return self::Jsonl === $this;
    }

    /**
     * @phpstan-assert-if-true self::Php $this
     */
    public function isPhp(): bool
    {
        return self::Php === $this;
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
     * @phpstan-assert-if-true self::Tiff $this
     */
    public function isTiff(): bool
    {
        return self::Tiff === $this;
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
     * @phpstan-assert-if-true self::Xlsx $this
     */
    public function isXlsx(): bool
    {
        return self::Xlsx === $this;
    }

    /**
     * @phpstan-assert-if-true self::Xml $this
     */
    public function isXml(): bool
    {
        return self::Xml === $this;
    }

    /**
     * @phpstan-assert-if-true self::Zip $this
     */
    public function isZip(): bool
    {
        return self::Zip === $this;
    }

    /**
     * @phpstan-assert-if-true self::Other $this
     */
    public function isOther(): bool
    {
        return self::Other === $this;
    }
}
