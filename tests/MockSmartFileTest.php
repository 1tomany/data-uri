<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\MockSmartFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
final class MockSmartFileTest extends TestCase
{
    use TestFileTrait;

    public function testConstructingMockSmartFileWithEmptyArguments(): void
    {
        $file = new MockSmartFile();

        // Assert: MockSmartFile Is Hydrated
        $this->assertNotEmpty($file->fingerprint);
        $this->assertNotEmpty($file->mediaType);
        $this->assertNotEmpty($file->byteCount);
        $this->assertNotEmpty($file->filePath);
        $this->assertNotEmpty($file->fileName);
        $this->assertNotEmpty($file->clientName);
        $this->assertNotEmpty($file->extension);
        $this->assertNotEmpty($file->remoteKey);
    }

    public function testConstructingMockSmartFileWithNonEmptyFilePathArgument(): void
    {
        $data = $this->fetchRandomFile();

        // Assert: File Exists
        $this->assertFileExists($data->filePath);

        // Act: Create MockSmartFile
        $file = new MockSmartFile(...[
            'filePath' => $data->filePath,
        ]);

        // Assert: MockSmartFile Has Same Path and Name
        $this->assertEquals($data->filePath, $file->filePath);
        $this->assertEquals($data->fileName, $file->fileName);
        $this->assertEquals($data->fileName, $file->clientName);
    }
}
