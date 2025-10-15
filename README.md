# Data URI Parser for PHP
This simple library exposes three functions that allow you to easily convert a wide variety of data into a temporary file saved on the local filesystem represented by a lightweight immutable value object.

## Installation
```
composer require 1tomany/data-uri
```

## API Overview
The three functions exposed by this library are:

* `OneToMany\DataUri\parse_data()`
* `OneToMany\DataUri\parse_base64_data()`
* `OneToMany\DataUri\parse_text_data()`

Each function returns an object that implements the `OneToMany\DataUri\Contract\Record\SmartFileInterface` interface.

The `parse_data()` function is the most versatile as it allows for a wide variety of inputs:

* A Data URL string as defined in [RFC2397](https://www.rfc-editor.org/rfc/rfc2397.html)
* A publicly accessible HTTP or HTTPS URL
* An existing and readable file

### `parse_data()`
The `parse_data()` function has the following arguments:

* `mixed $data` The data to parse.
* `?string $name` The display name for the temporary file. This is useful for handling file uploads where the original filename is preferred over the random name PHP assigns. A randomly generated name will be used if this is empty and a name cannot be resolved. This is `null` by default.
* `?string $directory` The directory to save the temporary file in. If empty, the temporary file is saved in the directory defined by `sys_get_temp_dir()`. This is `null` by default.
* `bool $deleteOriginal` If a file was used as the data to parse, and this is `true`, the original file will be deleted after the temporary one is created. This is `false` by default.
* `bool $selfDestruct` Indicates to the object created if it should self destruct or not. This is `true` by default.
* `?Filesystem $filesystem` An instance of the Symfony Filesystem component. This is useful if you wish to use `parse_data()` in tests and want to mock the Symfony Filesystem component.

#### Inside `parse_data()`
Under the hood, `parse_data()` uses the `fopen()` function, which means the data passed to it can be any [stream](https://www.php.net/manual/en/wrappers.php) that PHP supports.

### `parse_base64_data()`
This function is to be used when the data is assumed to be base64 encoded but NOT encoded as a Data URL.

The `parse_base64_data()` function has the following arguments:

* `string $data` The base64 encoded data to parse.
* `string $type` The MIME type of the data to parse.
* `?string $name` See `parse_data()`.
* `?string $directory` See `parse_data()`.
* `bool $selfDestruct` See `parse_data()`.
* `?Filesystem $filesystem` See `parse_data()`.

### `parse_text_data()`
This function is to be used when the data is plaintext.

The `parse_text_data()` function has the following arguments:

* `string $text` The plaintext data to parse.
* `?string $name` See `parse_data()`.
* `?string $directory` See `parse_data()`.
* `bool $selfDestruct` See `parse_data()`.
* `?Filesystem $filesystem` See `parse_data()`.

**Note:** The `parse_base64_data()` and `parse_text_data()` functions do not have the `$deleteOriginal` argument because they are working from the assumption that the data will be a string of text that already exists in memory.

## Example

See the [`parse_example.php`](https://github.com/1tomany/data-uri/blob/main/examples/parse_example.php) file for examples on how to use the `parse_data()` method.

### Testing with `SmartFile::createMock()`
You may be reluctant to write tests for code that uses the `parse_data()` function because it interacts with the actual filesystem. For example, if you have a class that takes a `SmartFileInterface` object and uploads it to a remote storage service, you may not want to actually call `parse_data()` in your test or instantiate a new `SmartFileInterface` object since it requires the existence of a file on the local filesystem and will attempt to delete the file when the object is destroyed.

In those instances, you can instantiate a `OneToMany\DataUri\SmartFile` object with the `createMock(string $path, string $mimeType)` method. `SmartFile` instances created using `createMock()` do not require the file to exist, and don't attempt to delete the file when the object is destroyed.

If you _do_ wish to use the `parse_data()` function, you can write a unit test that does not interact with the filesystem by passing a mocked `Symfony\Component\Filesystem\Filesystem` object as the last parameter of the `parse_data()` method in your test. You will need to mock the following methods of the `Filesystem` class:

* `string tempnam(string $dir, string $prefix, string $suffix = '')`
* `void dumpFile(string $filename, string|resource $content)`
* `void rename(string $origin, string $target, bool $overwrite = false)`

An example of the mocked `Filesystem` class can be found in the `ParseDataTest` test class in the [`testParsingDataRequiresWritingDataToTemporaryFile()` test case](https://github.com/1tomany/data-uri/blob/main/tests/ParseDataTest.php#L110).

If you want to take your tests further, you can validate the data is "written" to the temporary file by combining the mocked `Filesystem` object with a library like [mikey179/vfsstream](https://packagist.org/packages/mikey179/vfsstream).

## Credits

- [Vic Cherubini](https://github.com/viccherubini), [1:N Labs, LLC](https://1tomany.com)

## License

The MIT License
