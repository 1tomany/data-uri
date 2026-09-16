<?php

namespace OneToMany\DataUri\Tests;

use OneToMany\DataUri\Command\EncodeFileCommand;
use OneToMany\DataUri\DataDecoder;
use OneToMany\DataUri\DataUriBundle;
use OneToMany\DataUri\Serializer\TemporaryFileNormalizer;
use OneToMany\DataUri\Tests\Fixture\DataDecoderConsumer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[Group('UnitTests')]
final class DataUriBundleTest extends TestCase
{
    public function testGettingExtensionAlias(): void
    {
        $this->assertSame('onetomany_datauri', new DataUriBundle()->getContainerExtension()?->getAlias());
    }

    public function testRegisteringServices(): void
    {
        $container = $this->loadExtension();

        $this->assertTrue($container->hasDefinition(DataDecoder::class));
        $this->assertTrue($container->hasDefinition(EncodeFileCommand::class));
        $this->assertTrue($container->hasDefinition(TemporaryFileNormalizer::class));
        $this->assertTrue($container->getDefinition(EncodeFileCommand::class)->hasTag('console.command'));
        $this->assertTrue($container->getDefinition(TemporaryFileNormalizer::class)->hasTag('serializer.denormalizer'));
        $this->assertTrue($container->getDefinition(TemporaryFileNormalizer::class)->hasTag('serializer.normalizer'));
    }

    public function testDataDecoderCanBeAutowired(): void
    {
        $container = $this->loadExtension();

        $container
            ->register(DataDecoderConsumer::class)
            ->setAutowired(true)
            ->setPublic(true);

        $container->compile();

        $consumer = $container->get(DataDecoderConsumer::class);

        $this->assertInstanceOf(DataDecoderConsumer::class, $consumer);
        $this->assertInstanceOf(DataDecoder::class, $consumer->dataDecoder);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function loadExtension(array $config = []): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $extension = new DataUriBundle()->getContainerExtension();

        $this->assertNotNull($extension);
        $extension->load([$config], $container);

        return $container;
    }
}
