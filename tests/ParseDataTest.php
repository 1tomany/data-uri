<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Exception\DecodingDataFailedException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function OneToMany\DataUri\parse_data;

#[Group('UnitTests')]
final class ParseDataTest extends TestCase
{
    public function testDecodingDataRequiresValidDataUriPrefixOrFile(): void
    {
        $this->expectException(DecodingDataFailedException::class);

        parse_data('invalid-data-string');
    }
}
