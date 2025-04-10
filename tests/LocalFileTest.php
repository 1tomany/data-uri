<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Exception\EncodingFailedInvalidFilePathException;
use OneToMany\DataUri\LocalFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function OneToMany\DataUri\parse_data;

#[Group('UnitTests')]
final class LocalFileTest extends TestCase
{
    public function testDestructorDeletesTemporaryFile(): void
    {
        $localFile = parse_data(__DIR__.'/data/php-logo.png');
        $filePath = $localFile->filePath;

        $this->assertFileExists($filePath);

        unset($localFile);
        $this->assertFileDoesNotExist($filePath);
    }

    public function testToStringReturnsFilePath(): void
    {
        $localFile = parse_data(__DIR__.'/data/php-logo.png');
        $this->assertEquals($localFile->filePath, $localFile->__toString());
    }

    public function testToDataUriRequiresPathToExist(): void
    {
        $this->expectException(EncodingFailedInvalidFilePathException::class);

        new LocalFile('fingerprint1', '', 1, '/invalid/path/1.txt', '1.txt', 'txt', '')->toDataUri();
    }

    public function testObjectsWithDifferentFingerprintsAreNotEqual(): void
    {
        $localFile1 = new LocalFile('fingerprint1', '', 1, '1.txt', '1.txt', 'txt', '');
        $localFile2 = new LocalFile('fingerprint2', '', 1, '2.txt', '2.txt', 'txt', '');

        $this->assertFalse($localFile1->equals($localFile2));
        $this->assertFalse($localFile2->equals($localFile1));
    }

    public function testObjectsWithIdenticalFingerprintsAreLooselyEqual(): void
    {
        $localFile1 = new LocalFile('fingerprint1', '', 1, '1.txt', '1.txt', 'txt', '');
        $localFile2 = new LocalFile('fingerprint1', '', 1, '2.txt', '2.txt', 'txt', '');

        $this->assertTrue($localFile1->equals($localFile2));
        $this->assertTrue($localFile2->equals($localFile1));
    }

    public function testObjectsWithIdenticalFingerprintsAndPathsAreStrictlyEqual(): void
    {
        $localFile1 = new LocalFile('fingerprint1', '', 1, '1.txt', '1.txt', 'txt', '');
        $localFile2 = new LocalFile('fingerprint1', '', 1, '1.txt', '1.txt', 'txt', '');

        $this->assertTrue($localFile1->equals($localFile2, true));
        $this->assertTrue($localFile2->equals($localFile1, true));
    }
}
