# Data URI Parser for PHP
This simple library exposes a single method, `parse_data()` that
allows you to easily parse base64 encoded data URIs or valid file
paths. If valid, a temporary, uniquely named file will be created
and an immutable value object named `DataUri` will be returned.

Instances of the `DataUri` object will attempt to cleanup by
deleting the temporary file it references upon destruction.

## Installation
```
composer require 1tomany/php-data-uri
```

## Example
See the [`examples/parse_example.php`](https://github.com)
file for examples on how to use the `parse_data()` method.

## Credits
- [Vic Cherubini](https://github.com/viccherubini), [1:N Labs, LLC](https://1tomany.com)

## License
The MIT License
