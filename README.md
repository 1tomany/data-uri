# Data URI Parser for PHP
This simple library exposes a single function, `OneToMany\DataUri\parse_data()` that allows you to easily convert strings formatted as a "data" URL outlined in [RFC2397](https://www.rfc-editor.org/rfc/rfc2397.html) to a file on the local filesystem. During parsing, a temporary, uniquely named file will be created with the decoded contents of the "data" URL. `parse_data()` will return an immutable value object of type `OneToMany\DataUri\SmartFile` which references the local file.

Beneath the hood, `parse_data()` uses the `fopen()` function, which means the `$data` string passed to it can be any [stream](https://www.php.net/manual/en/wrappers.php) that PHP supports.

By default, instances of the `SmartFile` object will attempt to delete the temporary file it references upon object destruction. You can change this behavior by setting the `$delete` argument of the `SmartFile` constructor to `false`. `SmartFile` objects created by `parse_data()` are automatically set to self-destruct.

## Installation
```
composer require 1tomany/data-uri
```

## Example
See the [`parse_example.php`](https://github.com/1tomany/data-uri/blob/main/examples/parse_example.php) file for examples on how to use the `parse_data()` method.

### Testing with `SmartFile::createMock()`
You may be reluctant to write tests for code that uses the `parse_data()` function because it interacts with the actual filesystem. For example, if you have a class that takes a `SmartFile` object and uploads it to a remote storage service, you may not want to actually call `parse_data()` in your test or instantiate a new `SmartFile` object since it requires the existence of a file on the local filesystem and will attempt to delete the file when the object is destroyed.

In those instances, you can instantiate a `OneToMany\DataUri\SmartFile` object with the `createMock(string $filePath, string $contentType)` method. `SmartFile` instances created using `createMock()` do not require the file to exist, and don't attempt to delete the file when the object is destroyed.

If you _do_ wish to use the `parse_data()` function, you can write a unit test that does not interact with the filesystem by passing a mocked `Symfony\Component\Filesystem\Filesystem` object as the last parameter of the `parse_data()` method in your test. You will need to mock the following methods of the `Filesystem` class:

- `string tempnam(string $dir, string $prefix, string $suffix = '')`
- `void dumpFile(string $filename, string|resource $content)`
- `void rename(string $origin, string $target, bool $overwrite = false)`

An example of the mocked `Filesystem` class can be found in the `ParseDataTest` test class in the [`testParsingDataRequiresWritingDataToTemporaryFile()` test case](https://github.com/1tomany/data-uri/blob/main/tests/ParseDataTest.php#L87).

If you want to take your tests further, you can validate the data is "written" to the temporary file by combining the mocked `Filesystem` object with a library like [mikey179/vfsstream](https://packagist.org/packages/mikey179/vfsstream).

## Credits
- [Vic Cherubini](https://github.com/viccherubini), [1:N Labs, LLC](https://1tomany.com)

## License
The MIT License
