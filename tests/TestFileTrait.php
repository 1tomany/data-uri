<?php

namespace OneToMany\DataUri\Tests;

use Symfony\Component\Filesystem\Filesystem;

use function glob;
use function sys_get_temp_dir;

trait TestFileTrait
{
    private ?Filesystem $filesystem = null;

    private function createTempFile(string $suffix = '.txt', ?string $contents = null): string
    {
        $path = $this->getFilesystem()->tempnam(sys_get_temp_dir(), $this->getTempFilePrefix(), $suffix);

        if (null !== $contents) {
            $this->getFilesystem()->dumpFile($path, $contents);
        }

        return $path;
    }

    private function cleanupTempFiles(): void
    {
        $files = glob(sys_get_temp_dir().'/'.$this->getTempFilePrefix());

        $this->getFilesystem()->remove($files ?: []);
    }

    private function getFilesystem(): Filesystem
    {
        $this->filesystem ??= new Filesystem();

        return $this->filesystem;
    }

    private function getTempFilePrefix(): string
    {
        return '__1n__test_';
    }
}
