<?php

namespace OneToMany\DataUri\Tests;

use Symfony\Component\Filesystem\Filesystem;

use function sys_get_temp_dir;

trait TestFileTrait
{
    private ?Filesystem $fs = null;

    private function createTempFile(string $suffix = '.txt', ?string $contents = null): string
    {
        $this->fs ??= new Filesystem();

        $path = $this->fs->tempnam(sys_get_temp_dir(), '__1n__test_', $suffix);

        if (null !== $contents) {
            $this->fs->dumpFile($path, $contents);
        }

        return $path;
    }
}
