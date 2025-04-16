# Data URI Parser for PHP
This simple library exposes a single function, `OneToMany\DataUri\parse_data()` that allows you to easily parse base64 encoded data URIs or valid file paths. During parsing, a temporary, uniquely named file will be created and an immutable value object of type `OneToMany\DataUri\LocalFile` will be returned.

Instances of the `LocalFile` object will attempt delete temporary file it references upon object destruction.

## Installation
```
composer require 1tomany/data-uri
```

## Example
See the [`parse_example.php`](https://github.com/1tomany/data-uri/blob/main/examples/parse_example.php) file for examples on how to use the `parse_data()` method.

### Testing with `MockLocalFile`
You may be reluctant to write tests for code that uses the `parse_data()` function because it interacts with the actual filesystem. For example, if you have a class that takes a `LocalFile` object and uploads it to a remote storage service, you may not want to actually call `parse_data()` in your test or instantiate a new `LocalFile` object since it requires the existance of a file on the local filesystem and will attempt to delete the file when the object is destroyed.

In those instances, you can instantiate the `OneToMany\DataUri\MockLocalFile` object and use it in place of the `LocalFile` object. `MockLocalFile` extends `LocalFile`, allows you to artificially set any constructor values, and doesn't attempt to delete itself after it is destroyed.

If you _do_ wish to use the `parse_data()` function, you can write a unit test that does not interact with the filesystem by passing a mocked `Symfony\Component\Filesystem\Filesystem` object as the last parameter of the `parse_data()` method in your test. You will need to mock the following methods of the `Filesystem` class:

- `string readFile(string $filename)`
- `string tempnam(string $dir, string $prefix, string $suffix = '')`
- `void dumpFile(string $filename, string|resource $content)`
- `void rename(string $origin, string $target, bool $overwrite = false)`

You can validate the data is "written" to the temporary file by combining the mocked `Filesystem` object with a library like [mikey179/vfsstream](https://packagist.org/packages/mikey179/vfsstream).

## Credits
- [Vic Cherubini](https://github.com/viccherubini), [1:N Labs, LLC](https://1tomany.com)

## License
The MIT License
