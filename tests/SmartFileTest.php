<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Exception\ConstructionFailedFileDoesNotExistException;
use OneToMany\DataUri\Exception\ConstructionFailedFilePathNotProvidedException;
use OneToMany\DataUri\Exception\EncodingFailedFileDoesNotExistException;
use OneToMany\DataUri\Exception\ReadingFailedFileDoesNotExistException;
use OneToMany\DataUri\SmartFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function unlink;

#[Group('UnitTests')]
final class SmartFileTest extends TestCase
{
    use TestFileTrait;

    public function testConstructorRequiresNonEmptyFilePath(): void
    {
        $this->expectException(ConstructionFailedFilePathNotProvidedException::class);

        new SmartFile('', null, 'text/plain');
    }

    public function testConstructorRequiresFileToExistWhenCheckExistsIsTrue(): void
    {
        $this->expectException(ConstructionFailedFileDoesNotExistException::class);

        new SmartFile('/invalid/path/to/file.txt', null, 'text/plain');
    }

    public function testConstructorSetsDisplayNameToFileNameWhenDisplayNameIsNull(): void
    {
        $data = $this->fetchRandomFile();

        // Act: Construct SmartFile With Null Display Name
        $file = new SmartFile($data->filePath, null, $data->contentType, null, null, true, false);

        // Assert: File Name and Display Name Are Equal
        $this->assertEquals($file->fileName, $file->displayName);
    }

    public function testConstructorSetsDisplayNameWhenDisplayNameIsNotNull(): void
    {
        $data = $this->fetchRandomFile();

        // Act: Construct SmartFile With Non-Null Display Name
        $file = new SmartFile($data->filePath, null, $data->contentType, null, $data->displayName, true, false);

        // Assert: File Name and Display Name Are Different
        $this->assertEquals($data->displayName, $file->displayName);
        $this->assertNotEquals($data->displayName, $file->fileName);
        $this->assertNotEquals($file->displayName, $file->fileName);
    }

    public function testConstructorGeneratesRemoteKey(): void
    {
        $data = $this->fetchRandomFile();

        // Act: Construct SmartFile With Null Remote Key
        $file = new SmartFile($data->filePath, null, $data->contentType, null, null, true, false);

        // Assert: Remote Key Generated
        $this->assertMatchesRegularExpression('/^[a-z0-9]{2}\/[a-z0-9]{2}\/[a-z0-9]+\.[a-z0-9]+$/', $file->remoteKey);
    }

    public function testDestructorDeletesTemporaryFileWhenSelfDestructIsTrue(): void
    {
        $data = $this->fetchRandomFile();

        // Act: Construct SmartFile to Self Destruct
        $file = new SmartFile($data->filePath, null, $data->contentType, null, null, true, true);

        // Assert: SmartFile to Self Destruct
        $this->assertTrue($file->selfDestruct);
        $this->assertFileExists($file->filePath);

        // Act: Self Destruct
        $file->__destruct();

        // Assert: Destructor Deleted File
        $this->assertFileDoesNotExist($file->filePath);
    }

    public function testDestructorDoesNotDeleteTemporaryFileWhenFileAlreadyDeleted(): void
    {
        $data = $this->fetchRandomFile();

        // Act: Construct SmartFile to Self Destruct
        $file = new SmartFile($data->filePath, null, $data->contentType, null, null, true, true);

        // Assert: SmartFile to Self Destruct
        $this->assertTrue($file->selfDestruct);
        $this->assertFileExists($file->filePath);

        // Act: Manually Delete File
        unlink($file->filePath);
        $this->assertFileDoesNotExist($file->filePath);

        // Act: Self Destruct
        $file->__destruct();

        // Assert: Destructor Can Not Delete File
        $this->assertFileDoesNotExist($file->filePath);
    }

    public function testDestructorDoesNotDeleteTemporaryFileWhenSelfDestructIsFalse(): void
    {
        $data = $this->fetchRandomFile();

        // Act: Construct SmartFile to Not Self Destruct
        $file = new SmartFile($data->filePath, null, $data->contentType, null, null, true, false);

        // Assert: SmartFile to Not Self Destruct
        $this->assertFalse($file->selfDestruct);
        $this->assertFileExists($file->filePath);

        // Act: Self Destruct
        $file->__destruct();

        // Assert: Destructor Does Not Delete File
        $this->assertFileExists($file->filePath);

        unlink($file->filePath);
    }

    public function testToStringReturnsFilePath(): void
    {
        $file = new SmartFile('file.txt', 'hash', 'text/plain', 0, null, false);

        $this->assertEquals($file->filePath, $file->__toString());
    }

    public function testReadingFileRequiresFileToExist(): void
    {
        $this->expectException(ReadingFailedFileDoesNotExistException::class);

        new SmartFile('/invalid/path/1.txt', 'hash', 'text/plain', 0, null, false)->read();
    }

    public function testToDataUriRequiresFileToExist(): void
    {
        $this->expectException(EncodingFailedFileDoesNotExistException::class);

        new SmartFile('/invalid/path/1.txt', 'hash', 'text/plain', 0, null, false)->toDataUri();
    }

    public function testSmartFilesWithDifferentHashesAreNotEqual(): void
    {
        $file1 = new SmartFile('1.txt', 'hash1', 'text/plain', 0, null, false);
        $file2 = new SmartFile('1.txt', 'hash2', 'text/plain', 0, null, false);

        $this->assertFalse($file1->equals($file2));
        $this->assertFalse($file2->equals($file1));
    }

    public function testSmartFilesWithIdenticalHashesAreLooselyEqual(): void
    {
        $file1 = new SmartFile('1.txt', 'hash1', 'text/plain', 0, null, false);
        $file2 = new SmartFile('2.txt', 'hash1', 'text/plain', 0, null, false);

        $this->assertTrue($file1->equals($file2));
        $this->assertTrue($file2->equals($file1));
    }

    public function testSmartFilesWithIdenticalHashesAndPathsAreStrictlyEqual(): void
    {
        $file1 = new SmartFile('1.txt', 'hash1', 'text/plain', 0, null, false);
        $file2 = new SmartFile('1.txt', 'hash1', 'text/plain', 0, null, false);

        $this->assertTrue($file1->equals($file2, true));
        $this->assertTrue($file2->equals($file1, true));
    }
}
