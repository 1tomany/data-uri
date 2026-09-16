<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Command\EncodeFileCommand;
use OneToMany\DataUri\Serializer\TemporaryFileNormalizer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class DataUriBundle extends AbstractBundle
{
    protected string $extensionAlias = 'onetomany_datauri';

    /**
     * @see Symfony\Component\DependencyInjection\Extension\ConfigurableExtensionInterface
     *
     * @param array<string, mixed> $config
     */
    #[\Override]
    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder,
    ): void {
        $container
            ->services()
                ->set(DataDecoder::class)

                ->set(EncodeFileCommand::class)
                    ->arg('$dataDecoder', service(DataDecoder::class))
                    ->tag('console.command')

                ->set(TemporaryFileNormalizer::class)
                    ->arg('$dataDecoder', service(DataDecoder::class))
                    ->tag('serializer.denormalizer')
                    ->tag('serializer.normalizer')
        ;
    }
}
