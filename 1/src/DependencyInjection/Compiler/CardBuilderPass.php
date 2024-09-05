<?php

namespace CardPrinterService\DependencyInjection\Compiler;

use CardPrinterService\Service\Builder\CardImage\CardBuilderRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CardBuilderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(CardBuilderRegistry::class)) {
            return;
        }

        $definition = $container->findDefinition(CardBuilderRegistry::class);
        $taggedServices = $container->findTaggedServiceIds('app.builder.card');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
