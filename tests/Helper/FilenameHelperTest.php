<?php

namespace OneToMany\DataUri\Tests\Helper;

use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Helper\FilenameHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function random_int;

use const PHP_INT_MAX;

#[Group('UnitTests')]
#[Group('HelperTests')]
final class FilenameHelperTest extends TestCase
{
    public function testGeneratingFilenameRequiresNonZeroLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The length must be positive.');

        FilenameHelper::generate(0);
    }

    public function testGeneratingFilenameRequiresPositiveLength(): void
    {
        $fileNameLength = -random_int(0, 128);
        $this->assertLessThan(1, $fileNameLength);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The length must be positive.');

        FilenameHelper::generate($fileNameLength);
    }

    public function testGeneratingFilenameRequiresLengthLessThanOrEqualToMaximumFilenameLength(): void
    {
        $maximumLength = FilenameHelper::MAXIMUM_GENERATED_LENGTH;

        $filenameLength = random_int($maximumLength + 1, PHP_INT_MAX);
        $this->assertGreaterThan($maximumLength, $filenameLength);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The length must be less than or equal to '.$maximumLength.'.');

        FilenameHelper::generate($filenameLength);
    }

    #[DataProvider('providerFilenameAndNormalizedFilename')]
    public function testNormalizingFilename(?string $filename, ?string $normalizedFilename): void
    {
        $this->assertSame($normalizedFilename, FilenameHelper::normalize($filename));
    }

    public static function providerFilenameAndNormalizedFilename(): array
    {
        $provider = [
            [null, null],
            ['', null],
            [' ', null],
            ['/', null],
            ['-', null],
            ['_', null],
            ['\\', null],
            ['a', 'a'],
            ['A', 'A'],
            ['z', 'z'],
            ['Z', 'Z'],
            ['0', '0'],
            ['1', '1'],
            ['.pdf', null],
            ['/.pdf', null],
            ['/tmp', 'tmp'],
            ['a.jpeg', 'a.jpeg'],
            ['/a.jpeg', 'a.jpeg'],
            ['a/a.jpeg', 'a.jpeg'],
            ['~/a.jpeg', 'a.jpeg'],
            ['/tmp/data.txt', 'data.txt'],
            ['Heating - 2024-11 - Invoice OWN3086.pdf', 'Heating-2024-11-Invoice-OWN3086.pdf'],
        ];

        return $provider;
    }
}
