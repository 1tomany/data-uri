<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Exception\ConstructionFailedFileDoesNotExistException;
use OneToMany\DataUri\Exception\ConstructionFailedFilePathNotProvidedException;
use OneToMany\DataUri\Exception\EncodingFailedInvalidFilePathException;
use OneToMany\DataUri\SmartFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function sys_get_temp_dir;
use function tempnam;

#[Group('UnitTests')]
final class SmartFileTest extends TestCase
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

    public function testConstructorGeneratesRemoteKey(): void
    {
        $file = new SmartFile(__DIR__.'/data/php-logo.png', null, 'image/png', null, true, false);

        $this->assertMatchesRegularExpression('/^[a-z0-9]{2}\/[a-z0-9]{2}\/[a-z0-9]+\.[a-z0-9]+$/', $file->remoteKey);
    }

    public function testDestructorDeletesTemporaryFileWhenSelfDestructIsTrue(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), '__1n__smart_file_');

        $this->assertIsString($filePath);
        $this->assertFileExists($filePath);

        $file = new SmartFile(...[
            'filePath' => $filePath,
            'fingerprint' => 'fingerprint',
            'mediaType' => 'text/plain',
        ]);

        $this->assertTrue($file->selfDestruct);

        $file->__destruct();
        $this->assertFileDoesNotExist($filePath);
    }

    public function testToStringReturnsFilePath(): void
    {
        $file = new SmartFile('file.txt', 'fingerprint1', 'text/plain', 0, false, false);

        $this->assertEquals($file->filePath, $file->__toString());
    }

    public function testToDataUriRequiresFilePathToExist(): void
    {
        $this->expectException(EncodingFailedInvalidFilePathException::class);

        new SmartFile('/invalid/path/1.txt', 'fingerprint', 'text/plain', 0, false, false)->toDataUri();
    }

    public function testSmartFilesWithDifferentFingerprintsAreNotEqual(): void
    {
        $file1 = new SmartFile('1.txt', 'fingerprint1', 'text/plain', 0, false, false);
        $file2 = new SmartFile('1.txt', 'fingerprint2', 'text/plain', 0, false, false);

        $this->assertFalse($file1->equals($file2));
        $this->assertFalse($file2->equals($file1));
    }

    public function testSmartFilesWithIdenticalFingerprintsAreLooselyEqual(): void
    {
        $file1 = new SmartFile('1.txt', 'fingerprint1', 'text/plain', 0, false, false);
        $file2 = new SmartFile('2.txt', 'fingerprint1', 'text/plain', 0, false, false);

        $this->assertTrue($file1->equals($file2));
        $this->assertTrue($file2->equals($file1));
    }

    public function testSmartFilesWithIdenticalFingerprintsAndPathsAreStrictlyEqual(): void
    {
        $file1 = new SmartFile('1.txt', 'fingerprint1', 'text/plain', 0, false, false);
        $file2 = new SmartFile('1.txt', 'fingerprint1', 'text/plain', 0, false, false);

        $this->assertTrue($file1->equals($file2, true));
        $this->assertTrue($file2->equals($file1, true));
    }
}
