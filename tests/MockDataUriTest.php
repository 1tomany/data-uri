<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\MockDataUri;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
final class MockDataUriTest extends TestCase
{
    public function testEmptyConstructorGeneratesRandomValues(): void
    {
        $file = new MockDataUri();

        $this->assertNotEmpty($file->fingerprint);
        $this->assertNotEmpty($file->mediaType);
        $this->assertNotEmpty($file->byteCount);
        $this->assertNotEmpty($file->filePath);
        $this->assertNotEmpty($file->fileName);
        $this->assertNotEmpty($file->extension);
        $this->assertNotEmpty($file->remoteKey);
    }
}
