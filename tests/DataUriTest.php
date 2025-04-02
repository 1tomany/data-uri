<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\DataUri;
use OneToMany\DataUri\Exception\EncodingDataFailedException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
final class DataUriTest extends TestCase
{
    public function testDestructorDeletesFile(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), '__1n__test_');
        $data = new DataUri('', '', 0, $path, $path, '', '');

        $this->assertFileExists($path);

        unset($data);
        $this->assertFileDoesNotExist($path);
    }

    public function testAsUriRequiresPathToExist(): void
    {
        $this->expectException(EncodingDataFailedException::class);

        new DataUri('hash1', '', 1, '', '/invalid/path/1.txt', 'txt', '')->asUri();
    }

    public function testObjectsWithDifferentHashesAreNotEqual(): void
    {
        $data1 = new DataUri('hash1', '', 1, '', '1.txt', 'txt', '');
        $data2 = new DataUri('hash2', '', 1, '', '2.txt', 'txt', '');

        $this->assertFalse($data1->equals($data2));
        $this->assertFalse($data2->equals($data1));
    }

    public function testObjectsWithIdenticalHashesAreLooselyEqual(): void
    {
        $data1 = new DataUri('hash1', '', 1, '', '1.txt', 'txt', '');
        $data2 = new DataUri('hash1', '', 1, '', '2.txt', 'txt', '');

        $this->assertTrue($data1->equals($data2));
        $this->assertTrue($data2->equals($data1));
    }

    public function testObjectsWithIdenticalHashesAndPathsAreStrictlyEqual(): void
    {
        $data1 = new DataUri('hash1', '', 1, '', '1.txt', 'txt', '');
        $data2 = new DataUri('hash1', '', 1, '', '1.txt', 'txt', '');

        $this->assertTrue($data1->equals($data2, true));
        $this->assertTrue($data2->equals($data1, true));
    }
}
