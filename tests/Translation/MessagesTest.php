<?php

namespace OneToMany\DataUri\Tests\Translation;

use OneToMany\DataUri\Contract\Enum\FileType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function array_map;

#[Group('UnitTests')]
#[Group('TranslationTests')]
final class MessagesTest extends TestCase
{
    /**
     * @var array<non-empty-string>
     */
    private static array $messages = [];

    /**
     * @see PHPUnit\Framework\TestCase
     */
    #[\Override]
    public static function setUpBeforeClass(): void
    {
        static::$messages = require_once __DIR__.'/../../translations/messages.en.php'; // @phpstan-ignore assign.propertyType
    }

    #[DataProvider('providerFileTypeName')]
    public function testFileTypeNameIsMappedOntoItself(string $name): void
    {
        $this->assertArrayHasKey($name, static::$messages);
        $this->assertSame($name, static::$messages[$name]);
    }

    /**
     * @return non-empty-list<array{0: non-empty-string}>
     */
    public static function providerFileTypeName(): array
    {
        return array_map(static fn (FileType $ft): array => [$ft->getName()], FileType::cases());
    }
}
