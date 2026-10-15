<?php

namespace App\Shared\DependencyInjection;

use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Every handler of the core is reached through a RefusalTranslatingHandler, so that a use case refuses with a
 * UseCaseRefused (ADR 0035), and nobody builds one by hand (ADR 0044):
 * - a command handler is registered on the command bus through it; Messenger routes a message by the type of
 *   the argument of __invoke(), the decorator takes any object, so the command it handles is named explicitly;
 * - a query handler is injected through it, by the name of its intent: `RefusalTranslatingHandler
 *   $consultOrganization` for `ConsultOrganizationHandler`.
 */
final class RefusalTranslatingHandlersPass implements CompilerPassInterface
{
    public const string COMMAND_HANDLER = 'app.command_handler';
    public const string QUERY_HANDLER = 'app.query_handler';

    public function process(ContainerBuilder $container): void
    {
        foreach (array_keys($container->findTaggedServiceIds(self::COMMAND_HANDLER)) as $id) {
            $this->decorate($container, $id)
                ->addTag('messenger.message_handler', ['bus' => 'command.bus', 'handles' => $this->commandOf($this->classOf($container, $id))]);
        }

        foreach (array_keys($container->findTaggedServiceIds(self::QUERY_HANDLER)) as $id) {
            $this->decorate($container, $id);
            $container->registerAliasForArgument($id.'.refusal_translating', RefusalTranslatingHandler::class, $this->intentOf($this->classOf($container, $id)));
        }
    }

    private function decorate(ContainerBuilder $container, string $id): Definition
    {
        return $container->register($id.'.refusal_translating', RefusalTranslatingHandler::class)->setArguments([new Reference($id)]);
    }

    private function classOf(ContainerBuilder $container, string $id): string
    {
        return $container->getParameterBag()->resolveValue($container->getDefinition($id)->getClass() ?? $id);
    }

    /**
     * `ConsultOrganizationHandler` is the intent `consultOrganization`.
     */
    private function intentOf(string $handler): string
    {
        $shortName = substr($handler, (int) strrpos($handler, '\\') + 1);

        return lcfirst(substr($shortName, 0, -strlen('Handler')));
    }

    /**
     * @return class-string
     */
    private function commandOf(string $handler): string
    {
        $type = new \ReflectionMethod($handler, '__invoke')->getParameters()[0]->getType();

        return $type instanceof \ReflectionNamedType && class_exists($type->getName()) ? $type->getName() : throw new \LogicException(sprintf('%s::__invoke() must take one command', $handler));
    }
}
