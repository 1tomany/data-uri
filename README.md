# Data URI Parser for PHP

This simple library allows you to easily convert a wide variety of data into a temporary file represented by a lightweight immutable value object. It also includes Symfony bundle integration for autowiring, serialization, and console usage.

## Installation

```
composer require 1tomany/data-uri
```

## API Overview

The four methods exposed by this library are:

- `OneToMany\DataUri\DataDecoder::decode()`
- `OneToMany\DataUri\DataDecoder::decodeBase64()`
- `OneToMany\DataUri\DataDecoder::decodeText()`
- `OneToMany\DataUri\DataDecoder::fromMarkdown()`

Each method returns a `OneToMany\DataUri\Record\TemporaryFile` value object which implements the interface `OneToMany\DataUri\Contract\Record\TemporaryFileInterface`. By default, the `TemporaryFile` object will delete any filesystem resources it knows about when it is explicitly destructed or garbage collected.

The `DataDecoder::decode()` method is the most versatile as it allows for a wide variety of inputs:

- A data URI defined in [RFC2397](https://www.rfc-editor.org/rfc/rfc2397.html)
- A file from an accessible filesystem
- A public HTTP or HTTPS URL

### `DataDecoder::decode()`

Use this method when the type or source of data is unknown. This method has the following arguments:

- `mixed $data` The data, file, or URL to decode
- `string|FileType|null $type = null` The MIME type of the temporary file

    If empty, the type will be determined using either the file's extension or the `mime_content_type()` function. You may want to explicitly provide this argument when the file can be multiple types. For example, `mime_content_type()` may return `text/plain` for Markdown files, which is correct, however, you may wish to use the more specific MIME type `text/markdown`.

- `?string $name = null` The name for the temporary file

    This is useful for handling file uploads where the original filename is preferred over the random one PHP assigns. A randomly generated name will be used if this is empty and a name cannot be resolved.

#### Inside `DataDecoder::decode()`

Under the hood, the `decode()` method uses the `fopen()` and `stream_get_contents()` functions provided by the PHP standard library, which means the data passed to it can be any supported [stream](https://www.php.net/manual/en/wrappers.php).

### `DataDecoder::decodeBase64()`

Use this method when the data is known to be base64 encoded but NOT encoded as a data URI. This method has the following arguments:

- `?string $data` The base64 encoded string
- `string|FileType $type` The MIME type of the data
- `?string $name = null` See the `decode()` method

### `DataDecoder::decodeText()`

Use this method when the data is known to be plaintext. This method has the following arguments:

- `?string $text` The plaintext string
- `string|FileType $type = FileType::Txt` The MIME type of the text
- `?string $name = null` See the `decode()` method

### `DataDecoder::fromMarkdown()`

Use this method when the data is known to be Markdown. This method has the following arguments:

- `?string $markdown` The Markdown string
- `?string $name = null` See the `decode()` method

## Symfony Integration

The package includes `OneToMany\DataUri\DataUriBundle`. Symfony Flex enables it automatically after installation, and `OneToMany\DataUri\DataDecoder` can then be autowired without additional service configuration:

```php
<?php

use OneToMany\DataUri\DataDecoder;

final readonly class DecodeData
{
    public function __construct(
        private DataDecoder $dataDecoder,
    ) {
    }
}
```

No bundle configuration is necessary. The bundle also:

- registers `OneToMany\DataUri\Serializer\TemporaryFileNormalizer` with Symfony's Serializer component
- provides the `onetomany:data-uri:encode-file` console command for converting a file to a base64-encoded data URI

The framework-independent API remains available by constructing `DataDecoder` directly.

## Examples

Run and inspect the [`decode.php`](https://github.com/1tomany/data-uri/blob/master/examples/decode.php) file for examples on how to use the `DataDecoder::decode()` method.

```sh
php examples/decode.php [--all]
```

## Credits

- [Vic Cherubini](https://github.com/viccherubini), [1:N Labs, LLC](https://1tomany.com)

## License

The MIT License
