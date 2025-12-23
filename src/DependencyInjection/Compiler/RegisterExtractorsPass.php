<?php

namespace InSquare\PimcoreSimpleSearchBundle\DependencyInjection\Compiler;

use InSquare\PimcoreSimpleSearchBundle\Service\Extractor\ObjectContentExtractorInterface;
use InSquare\PimcoreSimpleSearchBundle\Service\Extractor\ObjectExtractorRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterExtractorsPass implements CompilerPassInterface
{
    private const TAG_NAME = 'insquare.search.object_extractor';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ObjectExtractorRegistry::class)) {
            return;
        }

        $registry = $container->findDefinition(ObjectExtractorRegistry::class);
        $taggedServices = $container->findTaggedServiceIds(self::TAG_NAME);

        $extractors = [];
        $classMapping = [];

        foreach ($taggedServices as $serviceId => $tags) {
            $definition = $container->findDefinition($serviceId);
            $class = $definition->getClass();

            if ($class === null) {
                throw new \RuntimeException(sprintf(
                    'Service "%s" tagged with "%s" must have a class defined.',
                    $serviceId,
                    self::TAG_NAME
                ));
            }

            $reflection = new \ReflectionClass($class);
            if (!$reflection->implementsInterface(ObjectContentExtractorInterface::class)) {
                throw new \RuntimeException(sprintf(
                    'Service "%s" must implement %s to be tagged with "%s".',
                    $serviceId,
                    ObjectContentExtractorInterface::class,
                    self::TAG_NAME
                ));
            }

            $extractors[$serviceId] = new Reference($serviceId);
        }

        $registry->setArgument('$extractors', $extractors);
    }
}
