<?php

namespace App\Shared\DependencyInjection;

use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingCommandHandler;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingQueryHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Every handler of the core is reached through the translation of its refusals, so that a use case refuses with a
 * UseCaseRefused (ADR 0035), and nobody builds one by hand (ADR 0044):
 * - a command handler is registered on the command bus through a RefusalTranslatingCommandHandler; Messenger routes
 *   a message by the type of the argument of __invoke(), the decorator takes any object, so the command it handles
 *   is named explicitly;
 * - a query handler is injected through a RefusalTranslatingQueryHandler, as a QueryHandler named after its intent:
 *   `QueryHandler $consultOrganization` for `ConsultOrganizationHandler`.
 */
final class RefusalTranslatingHandlersPass implements CompilerPassInterface
{
    public const string COMMAND_HANDLER = 'app.command_handler';
    public const string QUERY_HANDLER = 'app.query_handler';

    public function process(ContainerBuilder $container): void
    {
        foreach (array_keys($container->findTaggedServiceIds(self::COMMAND_HANDLER)) as $id) {
            $this->decorate($container, $id, RefusalTranslatingCommandHandler::class)
                ->addTag('messenger.message_handler', ['bus' => 'command.bus', 'handles' => $this->commandOf($this->classOf($container, $id))]);
        }

        foreach (array_keys($container->findTaggedServiceIds(self::QUERY_HANDLER)) as $id) {
            $this->decorate($container, $id, RefusalTranslatingQueryHandler::class);
            $container->registerAliasForArgument($id.'.refusal_translating', QueryHandler::class, $this->intentOf($this->classOf($container, $id)));
        }
    }

    /**
     * @param class-string<RefusalTranslatingCommandHandler|RefusalTranslatingQueryHandler> $decorator
     */
    private function decorate(ContainerBuilder $container, string $id, string $decorator): Definition
    {
        return $container->register($id.'.refusal_translating', $decorator)->setArguments([new Reference($id)]);
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
