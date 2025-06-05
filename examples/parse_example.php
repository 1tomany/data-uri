<?php

require_once __DIR__.'/../vendor/autoload.php';

// Parse a URL encoded data URI
$text = \OneToMany\DataUri\parse_data(data: 'data:text/plain,Hello%2C%20world%21', name: 'hello-world.txt');

// Parse a base64 encoded data URI
$data = \OneToMany\DataUri\parse_data(data: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAXCAYAAAARIY8tAAACgklEQVRIiX3UTWhWVxAG4Oe7uTGpms9U1MaIjVJFXBbcqN2JK7FQuhHErVK0m7bQRVe1C3XpotuK7cqdP4gLRQQ3CooIpYIhShXjT+pPtZKYfDUuzlz8PLk3AwfumZk77/vOmXNa3tsSfIl9WIcy/IOYipVbC4sjNoMOHuCPWM9akbgMh7EJv2EUb7EAv+IkztYALMIPOI0bKPApvsJTfFux+AZ/Ym0kVbYswL6uKV7Fz2Nz5v8M57C/QBvbcBR3g3llbfTiXgNAiYV4mfnHcAp7CvQFk+s1BdpR5HEDQG8Qmq6J3cNwEUl9mGwA6MXzeRR0GgBKTJVRoJCmZbgr4Q0GpDNqx3duq9AvdWCmJvashfW4iIcZk8u4KZ3NaIOCngB+6cOzE2THypAyju8w0ZXwCjtxC3sbAJqshQMYLKWLMhksn2SJSwL0dkOhHdiOn/C6y9+D/zBdhJQX5h5U1fumAybNez/+z/w9+AgTBVZKty4HqNTlqrptOJh2Mn+vNDQTBZbjX3OnoAIYnwdgRQDkCqq7NV5KPX5Yk7QgWDyaB6Ad5GZryA3gUYkLgbg6S/oYQ5E80gDwidSOPL48/JMt6Q5srVHQChXTNQwr65daW/fvK2wpJZm/hJKcxc84hPs1xQucwUFczWKr8CNmSqkVN3ElSxqRbug16ZXNrR3sr9f8u1FSPl1I85pPEKk9PQ0xQaxj7lMtak5hpsDfWCP1rdv6w5fPeGVDAV4H8Lk0nW8KHMduqW/dNiBd/yYFIw0AG/A9LuF1gRP4R5qmnVgaxVdKk/A29vlaI03PbOyXYpf0Co/hd10v7DCO4I7UktlYx/BF136+1cFf0sO3sCr8DtjXolip+GhdAAAAAElFTkSuQmCC', name: 'FieldSheet_Logo.png');

// Parse an existing file
$file = \OneToMany\DataUri\parse_data(__DIR__.'/fieldsheet.png');

print_r($text);
print_r($data);
print_r($file);

// Loose equality compares the fingerprints
assert($data->equals($file));
assert($file->equals($data));

// Strict equality compares both the fingerprints and filepaths
assert(false === $data->equals($file, true));
assert(false === $file->equals($data, true));

// Invokes destructors to delete temporary files
unset($data, $file);
