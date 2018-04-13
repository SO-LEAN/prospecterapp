<?php

namespace App\DependencyInjection;

use Solean\CleanProspecter\UseCase\UseCasesFacade;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class UseCasePass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container): void
    {
        $facadeDefinition = $container->getDefinition(UseCasesFacade::class);

        foreach ($container->findTaggedServiceIds('use_case') as $id => $tags) {
            $facadeDefinition->addMethodCall('addUseCase', [new Reference($id)]);
        }
    }
}
