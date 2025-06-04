<?php

namespace OneToMany\DataUri\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function OneToMany\DataUri\parse_base64_data;

#[Group('UnitTests')]
final class ParseBase64DataTest extends TestCase
{
    #[DataProvider('providerBase64DataAndMetadata')]
    public function testParsingBase64Data(string $data, int $size, string $type): void
    {
        $file = parse_base64_data($data, $type);

        $this->assertFileExists($file->path);
        $this->assertEquals($size, $file->size);
        $this->assertEquals($type, $file->type);
    }

    /**
     * @return list<list<int|non-empty-string>>
     */
    public static function providerBase64DataAndMetadata(): array
    {
        $provider = [
            ['eyJpZCI6MTB9', 9, 'application/json'],
            ['SGVsbG8sIHdvcmxkIQ==', 13, 'text/plain'],
            ['R0lGODdhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==', 43, 'image/gif'],
            ['iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVQImWNgAAIAAAUAAWJVMogAAAAASUVORK5CYII=', 68, 'image/png'],
        ];

        return $provider;
    }
}
