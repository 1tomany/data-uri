<?php

namespace OneToMany\DataUri\Tests;

use Symfony\Component\Filesystem\Filesystem;

use function sys_get_temp_dir;

trait TestFileTrait
{
    private function createTempFile(string $suffix = '.txt'): string
    {
        return new Filesystem()->tempnam(sys_get_temp_dir(), '__1n__test_', $suffix);
    }
}
