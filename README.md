# Data URI Parser for PHP
This simple library allows you to easily convert a wide variety of data into a temporary file represented by a lightweight immutable value object.

## Installation
```
composer require 1tomany/data-uri
```

## API Overview
The three methods exposed by this library are:

* `OneToMany\DataUri\DataDecoder::decode(mixed $data, ?string $name = null)`
* `OneToMany\DataUri\DataDecoder::decodeBase64(string $data, string $format, ?string $name = null)`
* `OneToMany\DataUri\DataDecoder::decodeText(string $text, ?string $name = null)`

Each method returns an object that implements the `OneToMany\DataUri\Contract\Record\DataUriInterface` interface.

The `DataDecoder::decode()` method is the most versatile as it allows for a wide variety of inputs:

* A data URI string as defined in [RFC2397](https://www.rfc-editor.org/rfc/rfc2397.html)
* A publicly accessible HTTP or HTTPS URL
* An existing and readable file

### `DataDecoder::decode()`
The `DataDecoder::decode()` method has the following parameters:

* `mixed $data` The data to decode
* `?string $name` The display name for the temporary file. This is useful for handling file uploads where the original filename is preferred over the random name PHP assigns. A randomly generated name will be used if this is empty and a name cannot be resolved. This is `null` by default.

#### Inside `DataDecoder::decode()`
Under the hood, `DataDecoder::decode()` uses the `fopen()` function, which means the data passed to it can be any [stream](https://www.php.net/manual/en/wrappers.php) that PHP supports.

### `DataDecoder::decodeBase64()`
This method is to be used when the data is known to be base64 encoded but NOT encoded as a data URI.

The `DataDecoder::decodeBase64()` method has the following parameters:

* `string $data` The base64 encoded string
* `string $format` The format of the data represented as a MIME type
* `?string $name` See `DataDecoder::decode()`

### `DataDecoder::decodeText()`
This method is to be used when the data is known to be plaintext.

The `DataDecoder::decodeText()` method has the following arguments:

* `string $text` The plaintext string
* `?string $name` See `DataDecoder::decode()`. The extension `.txt` will be appended to the `$name` if the value provided does not already use it.

## Examples
See the [`parse_example.php`](https://github.com/1tomany/data-uri/blob/main/examples/parse_example.php) file for examples on how to use the `DataDecoder::decode()` method.

## Credits
- [Vic Cherubini](https://github.com/viccherubini), [1:N Labs, LLC](https://1tomany.com)

## License
The MIT License
