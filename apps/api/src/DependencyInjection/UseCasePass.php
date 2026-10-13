<?php

namespace App\DependencyInjection;

use Prospecter\Core\UseCase\UseCasesFacade;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class UseCasePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $facadeDefinition = $container->getDefinition(UseCasesFacade::class);

        foreach ($container->findTaggedServiceIds('use_case') as $id => $tags) {
            $facadeDefinition->addMethodCall('addUseCase', [new Reference($id)]);
        }
    }
}
