<?php

namespace App\Shared\DependencyInjection;

use App\Security\AccessProblems;
use App\Shared\Mcp\RefusingTools;
use App\Shared\RefusalProblems;
use App\Shared\UnavailabilityProblems;
use Mcp\Server\Capability\Registry\ReferenceHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * The tools of the MCP server run through `RefusingTools` (ADR 0163): the builder of the server, which the bundle
 * gives the locator of the tools, runs them by a `ReferenceHandler` on that locator, the refusals translated around it.
 * After the pass of the bundle, which gives the builder its locator.
 */
final class RefusingToolsPass implements CompilerPassInterface
{
    private const string BUILDER = 'mcp.server.prospecter.builder';

    public function process(ContainerBuilder $container): void
    {
        $builder = $container->getDefinition(self::BUILDER);
        $builder->addMethodCall('setReferenceHandler', [new Definition(RefusingTools::class, [
            new Definition(ReferenceHandler::class, [$this->toolsOf($builder)]),
            new Reference(AccessProblems::class),
            new Reference(RefusalProblems::class),
            new Reference(UnavailabilityProblems::class),
            new Reference('serializer'),
        ])]);
    }

    private function toolsOf(Definition $builder): Reference
    {
        foreach ($builder->getMethodCalls() as [$method, $arguments]) {
            if ('setContainer' === $method) {
                return $arguments[0];
            }
        }

        throw new \LogicException(sprintf('The MCP bundle gave %s no locator of the tools.', self::BUILDER));
    }
}
