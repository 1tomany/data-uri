# Data URI Parser for PHP
This simple library exposes a single function, `\OneToMany\DataUri\parse_data()` that allows you to easily parse base64 encoded data URIs or valid file paths. During parsing, a temporary, uniquely named file will be created and an immutable value object of type `\OneToMany\DataUri\LocalFile` will be returned.

Instances of the `LocalFile` object will attempt delete temporary file it references upon object destruction.

## Installation
```
composer require 1tomany/data-uri
```

## Example
See the [`parse_example.php`](https://github.com/1tomany/data-uri/blob/main/examples/parse_example.php) file for examples on how to use the `parse_data()` method.

## Credits
- [Vic Cherubini](https://github.com/viccherubini), [1:N Labs, LLC](https://1tomany.com)

## License
The MIT License
