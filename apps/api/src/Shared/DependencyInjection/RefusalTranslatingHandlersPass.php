<?php

namespace App\Shared\DependencyInjection;

use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingCommandHandler;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingEventHandler;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingQueryHandler;
use Prospecter\Core\SharedKernel\Application\TransactionalCommandHandler;
use Prospecter\Core\SharedKernel\Application\TransactionalEventHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Every handler of the core is reached through the decorators of its kind, so that a use case refuses with a
 * UseCaseRefused (ADR 0035) and writes all or nothing, and nobody builds one by hand (ADR 0044). The translation
 * of the refusals is outside the unit of work, so that a refusal detected when it commits is translated too:
 * - a command handler is registered on the command bus, a reaction on the event bus; Messenger routes a message by
 *   the type of the argument of __invoke(), the decorators take any object, so the message handled is named
 *   explicitly, read on the handler itself;
 * - a query handler is injected as a QueryHandler named after its intent: `QueryHandler $consultOrganization` for
 *   `ConsultOrganizationHandler`.
 */
final class RefusalTranslatingHandlersPass implements CompilerPassInterface
{
    public const string COMMAND_HANDLER = 'app.command_handler';
    public const string QUERY_HANDLER = 'app.query_handler';
    public const string EVENT_HANDLER = 'app.event_handler';

    /** the decorators of a command handler, from the innermost to the outermost, by the suffix of their id */
    private const array COMMAND_DECORATORS = ['transactional' => TransactionalCommandHandler::class, 'refusal_translating' => RefusalTranslatingCommandHandler::class];
    private const array QUERY_DECORATORS = ['refusal_translating' => RefusalTranslatingQueryHandler::class];
    private const array EVENT_DECORATORS = ['transactional' => TransactionalEventHandler::class, 'refusal_translating' => RefusalTranslatingEventHandler::class];

    public function process(ContainerBuilder $container): void
    {
        foreach (array_keys($container->findTaggedServiceIds(self::COMMAND_HANDLER)) as $id) {
            $container->getDefinition($this->decorate($container, $id, self::COMMAND_DECORATORS))
                ->addTag('messenger.message_handler', ['bus' => 'command.bus', 'handles' => $this->messageOf($this->classOf($container, $id))]);
        }

        foreach (array_keys($container->findTaggedServiceIds(self::EVENT_HANDLER)) as $id) {
            $container->getDefinition($this->decorate($container, $id, self::EVENT_DECORATORS))
                ->addTag('messenger.message_handler', ['bus' => 'event.bus', 'handles' => $this->messageOf($this->classOf($container, $id))]);
        }

        foreach (array_keys($container->findTaggedServiceIds(self::QUERY_HANDLER)) as $id) {
            $container->registerAliasForArgument($this->decorate($container, $id, self::QUERY_DECORATORS), QueryHandler::class, $this->intentOf($this->classOf($container, $id)));
        }
    }

    /**
     * @param array<string, class-string> $decorators from the innermost to the outermost
     *
     * @return string the id of the outermost
     */
    private function decorate(ContainerBuilder $container, string $id, array $decorators): string
    {
        $decorated = $id;

        foreach ($decorators as $suffix => $decorator) {
            $container->register($id.'.'.$suffix, $decorator)->setAutowired(true)->setArgument('$handler', new Reference($decorated));
            $decorated = $id.'.'.$suffix;
        }

        return $decorated;
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
     * @return class-string the command or the event the handler takes
     */
    private function messageOf(string $handler): string
    {
        $type = new \ReflectionMethod($handler, '__invoke')->getParameters()[0]->getType();

        return $type instanceof \ReflectionNamedType && class_exists($type->getName()) ? $type->getName() : throw new \LogicException(sprintf('%s::__invoke() must take one message', $handler));
    }
}
