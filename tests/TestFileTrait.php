<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\SmartFile;

use function array_rand;
use function assert;
use function count;
use function glob;
use function sprintf;

trait TestFileTrait
{
    private function fetchRandomFile(): SmartFile
    {
        $files = glob(sprintf('%s/data/*.*', __DIR__));
        assert(false !== $files && 0 !== count($files));

        /** @var non-empty-string $path */
        $path = $files[array_rand($files)];

        return \OneToMany\DataUri\parse_data(data: $path, deleteOriginalFile: false);
    }

    protected function readFileContents(string $fileName): string
    {
        $path = sprintf('%s/data/%s', __DIR__, $fileName);

        return @file_get_contents($path) ?: throw new \RuntimeException(sprintf('Reading file "%s" failed.', $path));
    }
}
