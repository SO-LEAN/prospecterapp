<?php

declare(strict_types=1);

namespace App\Shared\DependencyInjection;

use App\Security\AccessProblems;
use App\Shared\Mcp\RefusingTools;
use App\Shared\RefusalProblems;
use App\Shared\Typed;
use App\Shared\UnavailabilityProblems;
use Mcp\Server\Capability\Registry\ReferenceHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * The MCP server's tools run through `RefusingTools` (ADR 0163). The bundle gives the tools' locator to the server's
 * builder. The builder runs the tools with a `ReferenceHandler` on that locator, and `RefusingTools` translates the
 * refusals around it. This pass runs after the bundle's pass, which gives the builder its locator.
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
            new Reference('logger'),
        ])]);
    }

    private function toolsOf(Definition $builder): Reference
    {
        foreach ($builder->getMethodCalls() as $call) {
            [$method, $arguments] = Typed::array($call);
            $tools = Typed::array($arguments)[0] ?? null;

            if ('setContainer' === $method && $tools instanceof Reference) {
                return $tools;
            }
        }

        throw new \LogicException(sprintf('The MCP bundle gave %s no locator of the tools.', self::BUILDER));
    }
}
