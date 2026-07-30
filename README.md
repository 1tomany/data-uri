# Data URI Parser for PHP

This simple library allows you to easily convert a wide variety of data into a temporary file represented by a lightweight immutable value object.

## Installation

```
composer require 1tomany/data-uri
```

## API Overview

The three methods exposed by this library are:

- `OneToMany\DataUri\DataDecoder::decode()`
- `OneToMany\DataUri\DataDecoder::decodeBase64()`
- `OneToMany\DataUri\DataDecoder::decodeText()`

Each method returns a `OneToMany\DataUri\Record\TemporaryFile` value object which implements the interface `OneToMany\DataUri\Contract\Record\TemporaryFileInterface`. By default, the `TemporaryFile` object will delete any filesystem resources it knows about when it is explicitly destructed or garbage collected.

The `DataDecoder::decode()` method is the most versatile as it allows for a wide variety of inputs:

- A data URI defined in [RFC2397](https://www.rfc-editor.org/rfc/rfc2397.html)
- A file from an accessible filesystem
- A public HTTP or HTTPS URL

### `DataDecoder::decode()`

The `DataDecoder::decode()` method has the following arguments:

- `mixed $data` The data, file, or URL to decode.
- `string|Type|null $type = null` The MIME type of the temporary file. If empty, the type will be determined using either the file's extension or the `mime_content_type()` function. You may want to explicitly provide this argument when the file can be multiple types. For example, `mime_content_type()` may return `text/plain` for Markdown files, which is correct, however, you may wish to use the more specific MIME type `text/markdown`.
- `string|null $name = null` The name for the temporary file. This is useful for handling file uploads where the original filename is preferred over the random one PHP assigns. A randomly generated name will be used if this is empty and a name cannot be resolved.

#### Inside `DataDecoder::decode()`

Under the hood, `DataDecoder::decode()` uses the `fopen()` and `stream_get_contents()` functions, which means the data passed to it can be any [stream](https://www.php.net/manual/en/wrappers.php) that PHP supports.

### `DataDecoder::decodeBase64()`

This method is to be used when the data is known to be base64 encoded but NOT encoded as a data URI.

The `DataDecoder::decodeBase64()` method has the following parameters:

- `string $data` The base64 encoded string.
- `string|Type $type` The MIME type of the data.
- `string|null $name = null` See `DataDecoder::decode()`.

### `DataDecoder::decodeText()`

This method is to be used when the data is known to be plaintext.

The `DataDecoder::decodeText()` method has the following arguments:

- `string $text` The plaintext string.
- `string|Type $type = Type::Txt` The MIME type of the text.
- `string|null $name = null` See `DataDecoder::decode()`.

## Examples

See the [`decode.php`](https://github.com/1tomany/data-uri/blob/main/examples/decode.php) file for examples on how to use the `DataDecoder::decode()` method.

## Credits

- [Vic Cherubini](https://github.com/viccherubini), [1:N Labs, LLC](https://1tomany.com)

## License

The MIT License
