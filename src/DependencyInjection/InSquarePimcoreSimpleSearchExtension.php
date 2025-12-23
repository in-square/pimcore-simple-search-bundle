<?php

namespace InSquare\PimcoreSimpleSearchBundle\DependencyInjection;

use InSquare\PimcoreSimpleSearchBundle\Service\Extractor\ObjectContentExtractorInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class InSquarePimcoreSimpleSearchExtension extends Extension
{
    public function getAlias(): string
    {
        return 'in_square_pimcore_simple_search';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerParameters($container, $config);

        $container->registerForAutoconfiguration(ObjectContentExtractorInterface::class)
            ->addTag('insquare.search.object_extractor');
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerParameters(ContainerBuilder $container, array $config): void
    {
        $prefix = 'in_square_pimcore_simple_search';

        $container->setParameter($prefix, $config);

        foreach ($config as $key => $value) {
            $container->setParameter(
                sprintf('%s.%s', $prefix, $key),
                $value
            );
        }
    }
}
