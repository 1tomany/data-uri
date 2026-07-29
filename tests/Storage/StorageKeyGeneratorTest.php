<?php

namespace OneToMany\DataUri\Tests\Storage;

use OneToMany\DataUri\DataDecoder;
use OneToMany\DataUri\Storage\StorageKeyGenerator;
use PHPUnit\Framework\TestCase;

final class StorageKeyGeneratorTest extends TestCase
{
    public function testKeysAreDeterministicAndIndependentOfTemporaryPaths(): void
    {
        $first = new DataDecoder()->decodeText('Hello', name: 'hello.txt');
        $second = new DataDecoder()->decodeText('Hello', name: 'hello.txt');
        $generator = new StorageKeyGenerator('uploads');

        self::assertNotSame($first->getPath(), $second->getPath());
        self::assertSame($generator->generate($first), $generator->generate($second));
        self::assertMatchesRegularExpression('#^uploads/[a-f0-9]{2}/[a-f0-9]{2}/hello\.txt$#', $generator->generate($first));
    }

    public function testGeneratedTemporaryNamesDoNotAffectKeys(): void
    {
        $first = new DataDecoder()->decodeText('Hello');
        $second = new DataDecoder()->decodeText('Hello');
        $generator = new StorageKeyGenerator();

        self::assertNotSame($first->getName(), $second->getName());
        self::assertSame($generator->generate($first), $generator->generate($second));
        self::assertStringEndsWith($first->getHash().'.txt', $generator->generate($first));
    }
}
