<?php

namespace OneToMany\DataUri\Tests;

use function array_map;
use function vsprintf;

$files = [
    [
        'filePath' => null,
        'fileName' => 'png-small.png',
        'mediaType' => 'image/png',
        'extension' => 'png',
    ],
    [
        'filePath' => null,
        'fileName' => 'text-small.txt',
        'mediaType' => 'text/plain',
        'extension' => 'txt',
    ],
    [
        'filePath' => null,
        'fileName' => 'word-small.docx',
        'mediaType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'extension' => 'docx',
    ],
];

$files = array_map(function (array $file): array {
    $filePath = vsprintf('%s/data/%s', [
        __DIR__, $file['fileName'],
    ]);

    return [...$file, ...['filePath' => $filePath]];
}, $files);

/* @return list<array{filePath: non-empty-string, fileName: non-empty-string, mediaType: non-empty-string, extension: non-empty-string}> */
return $files;
