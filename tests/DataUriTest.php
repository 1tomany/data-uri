<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\DataUri;
use OneToMany\DataUri\Exception\EncodingDataFailedException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function basename;
use function OneToMany\DataUri\parse_data;

#[Group('UnitTests')]
final class DataUriTest extends TestCase
{
    public function testDestructorDeletesTemporaryFile(): void
    {
        $dataUri = parse_data(__DIR__.'/data/php-logo.png');
        $filePath = $dataUri->filePath;

        $this->assertFileExists($filePath);

        unset($dataUri);
        $this->assertFileDoesNotExist($filePath);
    }

    public function testToStringReturnsFilePath(): void
    {
        $dataUri = parse_data(__DIR__.'/data/php-logo.png');
        $this->assertEquals($dataUri->filePath, strval($dataUri));
    }

    public function testGettingFileNameProperty(): void
    {
        $dataUri = parse_data(__DIR__.'/data/php-logo.png');
        $this->assertEquals(basename($dataUri->filePath), $dataUri->fileName);
    }

    public function testToDataUriRequiresPathToExist(): void
    {
        $this->expectException(EncodingDataFailedException::class);

        new DataUri('fingerprint1', '', 1, '/invalid/path/1.txt', '1.txt', 'txt', '')->toDataUri();
    }

    public function testObjectsWithDifferentFingerprintsAreNotEqual(): void
    {
        $data1 = new DataUri('fingerprint1', '', 1, '1.txt', '1.txt', 'txt', '');
        $data2 = new DataUri('fingerprint2', '', 1, '2.txt', '2.txt', 'txt', '');

        $this->assertFalse($data1->equals($data2));
        $this->assertFalse($data2->equals($data1));
    }

    public function testObjectsWithIdenticalFingerprintsAreLooselyEqual(): void
    {
        $data1 = new DataUri('fingerprint1', '', 1, '1.txt', '1.txt', 'txt', '');
        $data2 = new DataUri('fingerprint1', '', 1, '2.txt', '2.txt', 'txt', '');

        $this->assertTrue($data1->equals($data2));
        $this->assertTrue($data2->equals($data1));
    }

    public function testObjectsWithIdenticalFingerprintsAndPathsAreStrictlyEqual(): void
    {
        $data1 = new DataUri('fingerprint1', '', 1, '1.txt', '1.txt', 'txt', '');
        $data2 = new DataUri('fingerprint1', '', 1, '1.txt', '1.txt', 'txt', '');

        $this->assertTrue($data1->equals($data2, true));
        $this->assertTrue($data2->equals($data1, true));
    }
}
