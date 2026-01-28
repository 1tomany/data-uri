<?php

require_once __DIR__.'/../vendor/autoload.php';

use OneToMany\DataUri\DataDecoder;

$dataDecoder = new DataDecoder();

// Decode an ASCII encoded data URI and use "hello_world.txt" as the display name
$file1 = $dataDecoder->decode('data:text/plain,Hello%2C%20world%21', 'hello_world.txt');
print_r($file1);

var_dump($file1->hash);

// Decode a base64 encoded data URI and use "FieldSheetLogo.png" as the display name
$file2 = $dataDecoder->decode('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAXCAYAAAARIY8tAAACgklEQVRIiX3UTWhWVxAG4Oe7uTGpms9U1MaIjVJFXBbcqN2JK7FQuhHErVK0m7bQRVe1C3XpotuK7cqdP4gLRQQ3CooIpYIhShXjT+pPtZKYfDUuzlz8PLk3AwfumZk77/vOmXNa3tsSfIl9WIcy/IOYipVbC4sjNoMOHuCPWM9akbgMh7EJv2EUb7EAv+IkztYALMIPOI0bKPApvsJTfFux+AZ/Ym0kVbYswL6uKV7Fz2Nz5v8M57C/QBvbcBR3g3llbfTiXgNAiYV4mfnHcAp7CvQFk+s1BdpR5HEDQG8Qmq6J3cNwEUl9mGwA6MXzeRR0GgBKTJVRoJCmZbgr4Q0GpDNqx3duq9AvdWCmJvashfW4iIcZk8u4KZ3NaIOCngB+6cOzE2THypAyju8w0ZXwCjtxC3sbAJqshQMYLKWLMhksn2SJSwL0dkOhHdiOn/C6y9+D/zBdhJQX5h5U1fumAybNez/+z/w9+AgTBVZKty4HqNTlqrptOJh2Mn+vNDQTBZbjX3OnoAIYnwdgRQDkCqq7NV5KPX5Yk7QgWDyaB6Ad5GZryA3gUYkLgbg6S/oYQ5E80gDwidSOPL48/JMt6Q5srVHQChXTNQwr65daW/fvK2wpJZm/hJKcxc84hPs1xQucwUFczWKr8CNmSqkVN3ElSxqRbug16ZXNrR3sr9f8u1FSPl1I85pPEKk9PQ0xQaxj7lMtak5hpsDfWCP1rdv6w5fPeGVDAV4H8Lk0nW8KHMduqW/dNiBd/yYFIw0AG/A9LuF1gRP4R5qmnVgaxVdKk/A29vlaI03PbOyXYpf0Co/hd10v7DCO4I7UktlYx/BF136+1cFf0sO3sCr8DtjXolip+GhdAAAAAElFTkSuQmCC', 'FieldSheetLogo.png');
print_r($file2);

// Decode an existing file
$file3 = $dataDecoder->decode(__DIR__.'/fieldsheet.png');
print_r($file3);

// Decode base64 encoded data with a known format
$file4 = $dataDecoder->decodeBase64('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVQImWNgAAIAAAUAAWJVMogAAAAASUVORK5CYII=', 'image/png', '1x1.png');
print_r($file4);

// Decode plaintext and use "hello_world.txt" as the display name
$file5 = $dataDecoder->decodeText('Hello, world!', 'hello_world.txt');
print_r($file5);

// Decode an image URL
// $file6 = $dataDecoder->decode('https://assets.extract-cdn.com/data/ao-smith-label.jpg');
// print_r($file6);

// Loose equality compares hashes
assert($file2->equals($file3, false));
assert($file3->equals($file2, false));

// Strict equality compares hashes and paths
assert(false === $file2->equals($file3, true));
assert(false === $file3->equals($file2, true));

// Call the destructor to delete temporary files
unset($file1, $file2, $file3, $file4, $file5, $file6);
