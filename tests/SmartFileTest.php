<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Exception\ConstructionFailedFileDoesNotExistException;
use OneToMany\DataUri\Exception\ConstructionFailedFilePathNotProvidedException;
use OneToMany\DataUri\Exception\EncodingFailedInvalidFilePathException;
use OneToMany\DataUri\SmartFile;
use PHPUnit\Framework\Attributes\Group;

use function basename;
use function OneToMany\DataUri\parse_data;
use function unlink;

#[Group('UnitTests')]
final class SmartFileTest extends FileTestCase
{
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

    public function testConstructorSetsClientNameToFileNameWhenClientNameIsNull(): void
    {
        $file = new SmartFile($this->path, null, $this->type, null, null, true, false);

        $this->assertEquals($file->fileName, $file->clientName);
    }

    public function testConstructorSetsClientNameWhenClientNameIsNotNull(): void
    {
        $file = parse_data($this->path);
        $clientName = basename($this->path);

        $this->assertEquals($clientName, $file->clientName);
        $this->assertNotEquals($clientName, $file->fileName);
    }

    public function testConstructorGeneratesRemoteKey(): void
    {
        $file = new SmartFile($this->path, null, $this->type, null, null, true, false);

        $this->assertMatchesRegularExpression('/^[a-z0-9]{2}\/[a-z0-9]{2}\/[a-z0-9]+\.[a-z0-9]+$/', $file->remoteKey);
    }

    public function testDestructorDeletesTemporaryFileWhenSelfDestructIsTrue(): void
    {
        $file = parse_data($this->path);

        $this->assertTrue($file->selfDestruct);
        $this->assertFileExists($file->filePath);

        $file->__destruct();
        $this->assertFileDoesNotExist($file->filePath);
    }

    public function testDestructorDoesNotDeleteTemporaryFileWhenFileAlreadyDeleted(): void
    {
        $file = parse_data($this->path);

        $this->assertTrue($file->selfDestruct);
        $this->assertFileExists($file->filePath);

        unlink($file->filePath);
        $this->assertFileDoesNotExist($file->filePath);

        $file->__destruct();
        $this->assertFileDoesNotExist($file->filePath);
    }

    public function testDestructorDoesNotDeleteTemporaryFileWhenSelfDestructIsFalse(): void
    {
        $file = parse_data($this->path, selfDestruct: false);

        $this->assertFalse($file->selfDestruct);
        $this->assertFileExists($file->filePath);

        $file->__destruct();
        $this->assertFileExists($file->filePath);

        unlink($file->filePath);
    }

    public function testToStringReturnsFilePath(): void
    {
        $file = new SmartFile('file.txt', 'fingerprint1', 'text/plain', 0, null, false);

        $this->assertEquals($file->filePath, $file->__toString());
    }

    public function testToDataUriRequiresFilePathToExist(): void
    {
        $this->expectException(EncodingFailedInvalidFilePathException::class);

        new SmartFile('/invalid/path/1.txt', 'fingerprint', 'text/plain', 0, null, false)->toDataUri();
    }

    public function testSmartFilesWithDifferentFingerprintsAreNotEqual(): void
    {
        $file1 = new SmartFile('1.txt', 'fingerprint1', 'text/plain', 0, null, false);
        $file2 = new SmartFile('1.txt', 'fingerprint2', 'text/plain', 0, null, false);

        $this->assertFalse($file1->equals($file2));
        $this->assertFalse($file2->equals($file1));
    }

    public function testSmartFilesWithIdenticalFingerprintsAreLooselyEqual(): void
    {
        $file1 = new SmartFile('1.txt', 'fingerprint1', 'text/plain', 0, null, false);
        $file2 = new SmartFile('2.txt', 'fingerprint1', 'text/plain', 0, null, false);

        $this->assertTrue($file1->equals($file2));
        $this->assertTrue($file2->equals($file1));
    }

    public function testSmartFilesWithIdenticalFingerprintsAndPathsAreStrictlyEqual(): void
    {
        $file1 = new SmartFile('1.txt', 'fingerprint1', 'text/plain', 0, null, false);
        $file2 = new SmartFile('1.txt', 'fingerprint1', 'text/plain', 0, null, false);

        $this->assertTrue($file1->equals($file2, true));
        $this->assertTrue($file2->equals($file1, true));
    }
}
