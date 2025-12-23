<?php

namespace InSquare\PimcoreSimpleSearchBundle\DependencyInjection;

use InSquare\PimcoreSimpleSearchBundle\Service\Extractor\ObjectContentExtractorInterface;
use InSquare\PimcoreSimpleSearchBundle\Service\Extractor\ObjectExtractorProvider;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class InSquarePimcoreSimpleSearchExtension extends Extension
{
    public function getAlias(): string
    {
        return 'in_square_pimcore_simple_search';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        // Load service definitions
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yaml');

        // Process configuration
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Register all configuration as parameters
        $this->registerParameters($container, $config);

        // Auto-configure object extractors
        $container->registerForAutoconfiguration(ObjectContentExtractorInterface::class)
            ->addTag('insquare.search.object_extractor');
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerParameters(ContainerBuilder $container, array $config): void
    {
        $prefix = 'in_square_pimcore_simple_search';

        // Register full config
        $container->setParameter($prefix, $config);

        // Register individual parameters for easy access
        foreach ($config as $key => $value) {
            $container->setParameter(
                sprintf('%s.%s', $prefix, $key),
                $value
            );
        }
    }
}
