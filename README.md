# PHP Data URI Manipulator
This simple library allows you to manipulate base64 encoded
files (generally) following the specifications laid out in
RFC 2397. Additionally, this library uses only functions and
classes in the PHP Standard Library and Fileinfo extension.

## Installation
```
composer require 1tomany/php-data-uri
```

## Parsing Data
The simplest usage of the library allows you to parse a base64
encoded data URI string with the `parse_data()` function. This
function returns a `DataUri` object and also creates a temporary
file on the filesystem for you to handle. The `DataUri` class also
has a destructor that automatically deletes the temporary file
when the object is destructed.

```php
<?php

$dataUri = \OneToMany\DataUri\parse_data(
    'data:image/png;base64;MXRvbWFueS5jb20='
);
```

The `DataUri` class also implements the `\Stringable` interface, and
casting it to a string will return the path to the temporary file.

Finally, the `DataUri` class has a method named `asUri()` that will
convert the file back to a base64 encoded data URI.

## Parsing Files
You can also pass a file path to the `parse_data()` function and, assuming
the file exists and is readable, the same `DataUri` object will be returned.

```php
<?php

$dataUri = \OneToMany\DataUri\parse_data(
    '/home/vic/downloads/hey_man_nice_shot.jpeg'
);
```

## Credits
- [Vic Cherubini](https://github.com/viccherubini), [1:N Labs, LLC](https://1tomany.com)

## License
The MIT License
