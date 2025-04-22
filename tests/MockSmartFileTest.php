<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\MockSmartFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function sys_get_temp_dir;

#[Group('UnitTests')]
final class MockSmartFileTest extends TestCase
{
    public function testConstructingMockSmartFileWithEmptyArguments(): void
    {
        $file = new MockSmartFile();

        $this->assertNotEmpty($file->fingerprint);
        $this->assertNotEmpty($file->mediaType);
        $this->assertNotEmpty($file->byteCount);
        $this->assertNotEmpty($file->filePath);
        $this->assertNotEmpty($file->fileName);
        $this->assertNotEmpty($file->extension);
        $this->assertNotEmpty($file->remoteKey);
    }

    public function testConstructingMockSmartFileWithNonEmptyFilePathArgument(): void
    {
        $fileName = '__1n__mock_smart_file_123.temp';
        $filePath = sys_get_temp_dir().'/'.$fileName;

        $file = new MockSmartFile(...[
            'filePath' => $filePath,
        ]);

        $this->assertEquals($filePath, $file->filePath);
        $this->assertEquals($fileName, $file->fileName);
    }
}
