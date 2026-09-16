<?php

namespace OneToMany\DataUri\Tests\Command;

use OneToMany\DataUri\Command\EncodeFileCommand;
use OneToMany\DataUri\DataDecoder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[Group('UnitTests')]
#[Group('CommandTests')]
final class EncodeFileCommandTest extends TestCase
{
    public function testEncodingFile(): void
    {
        $commandTester = new CommandTester(new EncodeFileCommand());

        $this->assertSame(Command::SUCCESS, $commandTester->execute(['path' => __FILE__]));
        $this->assertSame(new DataDecoder()->decode(__FILE__)->toDataUri(), $commandTester->getDisplay());
    }
}
