<?php

namespace OneToMany\DataUri\Tests\Record;

use OneToMany\DataUri\Contract\Record\DataUriInterface;
use OneToMany\DataUri\DataDecoder;
use OneToMany\DataUri\Record\DataUri;
use PHPUnit\Framework\TestCase;

final class DataUriTest extends TestCase
{
    public function testDestructorDeletesTemporaryFile(): void
    {
        /** @var DataUri&DataUriInterface $file */
        $file = new DataDecoder()->decode(__DIR__.'/../data/pdf-small.pdf');
        $this->assertFileExists($file->getPath());

        $file->__destruct();
        $this->assertFileDoesNotExist($file->getPath());
    }
}
