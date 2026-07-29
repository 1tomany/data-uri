<?php

namespace OneToMany\DataUri\Tests\Helper;

use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Helper\FilenameHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function strlen;

final class FilenameHelperTest extends TestCase
{
    #[DataProvider('provideUnsafeNames')]
    public function testSanitizingNames(?string $name, ?string $expected): void
    {
        self::assertSame($expected, FilenameHelper::sanitize($name));
    }

    /**
     * @return list<array{?string, ?string}>
     */
    public static function provideUnsafeNames(): array
    {
        return [
            [null, null],
            ['', null],
            ['.', null],
            ['..', null],
            ['...', null],
            ['0', '0'],
            ['../invoice.pdf', 'invoice.pdf'],
            ['Résumé 2026.pdf', 'Résumé_2026.pdf'],
            ['hello/world.txt', 'world.txt'],
        ];
    }

    public function testSanitizedNamesAreLengthLimited(): void
    {
        $name = FilenameHelper::sanitize(str_repeat('é', 200));

        self::assertNotNull($name);
        self::assertLessThanOrEqual(180, strlen($name));
    }

    public function testGeneratingNamesRequiresAPositiveLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be greater than zero');

        FilenameHelper::generate(0);
    }

    public function testChangingExtensionsAppendsOnlyValidatedSegments(): void
    {
        self::assertSame('report.txt', FilenameHelper::changeExtension('report', 'TXT'));
        self::assertSame('report.txt', FilenameHelper::changeExtension('report.TXT', 'txt'));
        self::assertSame('report.csv.txt', FilenameHelper::changeExtension('report.csv', 'txt'));
    }

    public function testChangingExtensionsRejectsPathCharacters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The extension "../txt" is invalid.');

        FilenameHelper::changeExtension('report', '../txt');
    }
}
