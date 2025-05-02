<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\SmartFile;

use function OneToMany\DataUri\parse_data;

trait TestFileTrait
{
    private function fetchRandomFile(?string $fileName = null): SmartFile
    {
        $files = \glob(\sprintf(__DIR__.'/data/%s', $fileName ?? '*.*'));

        if (!$files || 0 === count($files)) {
            throw new \RuntimeException('no files');
        }

        return parse_data(data: $files[\array_rand($files)], deleteOriginalFile: false);
    }

    protected function readFileContents(string $fileName): string
    {
        return @file_get_contents(__DIR__.'/data/'.$fileName) ?: throw new \RuntimeException(sprintf('Failed to read "%s".', $fileName));
    }
}
