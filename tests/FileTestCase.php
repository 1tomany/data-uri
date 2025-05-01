<?php

namespace OneToMany\DataUri\Tests;

use PHPUnit\Framework\TestCase;

use function mime_content_type;

abstract class FileTestCase extends TestCase
{
    protected string $path;
    protected string $type;

    protected function setUp(): void
    {
        $this->path = __DIR__.'/data/php-logo.png';

        // @phpstan-ignore-next-line
        $this->type = mime_content_type($this->path);
    }
}
