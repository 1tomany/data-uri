#!/usr/bin/env php
<?php

require_once __DIR__.'/../vendor/autoload.php';

use OneToMany\DataUri\Contract\Enum\FileType;
use OneToMany\DataUri\DataDecoder;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\SingleCommandApplication;
use Symfony\Component\Console\Style\SymfonyStyle;

$command = function (
    SymfonyStyle $io,
    #[Option('Show all examples')] bool $all = false,
): int {
    $io->title('data-uri Examples');

    $dataDecoder = new DataDecoder();

    $tempFiles = [];
    $tableRows = [];

    // Decode an ASCII encoded data URI with a randomly generated file name
    $tempFiles[] = $dataDecoder->decode('data:text/plain,Hello%2C%20world%21');

    // Decode a base64 encoded data URI and use "logo.png" as the name
    $tempFiles[] = $dataDecoder->decode('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAXCAYAAAARIY8tAAACgklEQVRIiX3UTWhWVxAG4Oe7uTGpms9U1MaIjVJFXBbcqN2JK7FQuhHErVK0m7bQRVe1C3XpotuK7cqdP4gLRQQ3CooIpYIhShXjT+pPtZKYfDUuzlz8PLk3AwfumZk77/vOmXNa3tsSfIl9WIcy/IOYipVbC4sjNoMOHuCPWM9akbgMh7EJv2EUb7EAv+IkztYALMIPOI0bKPApvsJTfFux+AZ/Ym0kVbYswL6uKV7Fz2Nz5v8M57C/QBvbcBR3g3llbfTiXgNAiYV4mfnHcAp7CvQFk+s1BdpR5HEDQG8Qmq6J3cNwEUl9mGwA6MXzeRR0GgBKTJVRoJCmZbgr4Q0GpDNqx3duq9AvdWCmJvashfW4iIcZk8u4KZ3NaIOCngB+6cOzE2THypAyju8w0ZXwCjtxC3sbAJqshQMYLKWLMhksn2SJSwL0dkOhHdiOn/C6y9+D/zBdhJQX5h5U1fumAybNez/+z/w9+AgTBVZKty4HqNTlqrptOJh2Mn+vNDQTBZbjX3OnoAIYnwdgRQDkCqq7NV5KPX5Yk7QgWDyaB6Ad5GZryA3gUYkLgbg6S/oYQ5E80gDwidSOPL48/JMt6Q5srVHQChXTNQwr65daW/fvK2wpJZm/hJKcxc84hPs1xQucwUFczWKr8CNmSqkVN3ElSxqRbug16ZXNrR3sr9f8u1FSPl1I85pPEKk9PQ0xQaxj7lMtak5hpsDfWCP1rdv6w5fPeGVDAV4H8Lk0nW8KHMduqW/dNiBd/yYFIw0AG/A9LuF1gRP4R5qmnVgaxVdKk/A29vlaI03PbOyXYpf0Co/hd10v7DCO4I7UktlYx/BF136+1cFf0sO3sCr8DtjXolip+GhdAAAAAElFTkSuQmCC', name: 'logo.png');

    // Decode a base64 encoded data URI without a name
    $tempFiles[] = $dataDecoder->decode('data:application/pdf;base64,JVBERi0xLg10cmFpbGVyPDwvUm9vdDw8L1BhZ2VzPDwvS2lkc1s8PC9NZWRpYUJveFswIDAgMyAzXT4+XT4+Pj4+Pg==');

    // Decode an existing file
    $tempFiles[] = $dataDecoder->decode(__DIR__.'/../config/files/ao-smith-label.jpeg');

    // Decode base64 encoded data with a known format
    $tempFiles[] = $dataDecoder->decodeBase64('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVQImWNgAAIAAAUAAWJVMogAAAAASUVORK5CYII=', 'image/png', '1x1.png');

    // Decode plaintext and use "hello_world.txt" as the display name
    $tempFiles[] = $dataDecoder->decodeText('Hello, world!', name: 'hello_world.txt');

    // Decode Markdown and use "hello_world.md" as the display name
    $tempFiles[] = $dataDecoder->decodeText('**Hello, world!**', FileType::Markdown, 'hello_world.md');

    // Decode from a URL
    if (true === $all) {
        $tempFiles[] = $dataDecoder->decode('https://assets.extract-cdn.com/data/ao-smith-label.jpg');
    }

    // Format and delete each file
    foreach ($tempFiles as $tempFile) {
        array_push($tableRows, [
            $tempFile->getPath(),
            $tempFile->getName(),
            $tempFile->getSize(),
            $tempFile->getType()->getName(),
            $tempFile->getFormat(),
            $tempFile->getKey(),
        ]);

        $tempFile->delete();
    }

    $io->table(['Path', 'Name', 'Size', 'Type', 'Format', 'Key'], [
        ...$tableRows,
    ]);

    return Command::SUCCESS;
};

new SingleCommandApplication()->setName('data-uri Examples')->setCode($command)->run();
