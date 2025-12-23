<?php

namespace InSquare\PimcoreSimpleSearchBundle;

use InSquare\PimcoreSimpleSearchBundle\DependencyInjection\Compiler\RegisterExtractorsPass;
use InSquare\PimcoreSimpleSearchBundle\DependencyInjection\InSquarePimcoreSimpleSearchExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class InSquarePimcoreSimpleSearchBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterExtractorsPass());
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        if ($this->extension === null) {
            $this->extension = new InSquarePimcoreSimpleSearchExtension();
        }

        return $this->extension;
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
