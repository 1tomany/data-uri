<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\MockLocalFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
final class MockLocalFileTest extends TestCase
{
    public function testEmptyConstructorGeneratesRandomValues(): void
    {
        $localFile = new MockLocalFile();

        $this->assertNotEmpty($localFile->fingerprint);
        $this->assertNotEmpty($localFile->mediaType);
        $this->assertNotEmpty($localFile->byteCount);
        $this->assertNotEmpty($localFile->filePath);
        $this->assertNotEmpty($localFile->fileName);
        $this->assertNotEmpty($localFile->extension);
        $this->assertNotEmpty($localFile->remoteKey);
    }
}
