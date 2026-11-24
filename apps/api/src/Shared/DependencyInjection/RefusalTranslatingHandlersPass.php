<?php

declare(strict_types=1);

namespace App\Shared\DependencyInjection;

use App\Shared\Typed;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Application\Handler\RefusalTranslatingCommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\RefusalTranslatingQueryHandler;
use Prospecter\Core\SharedKernel\Application\Handler\TransactionalCommandHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Every core command or query handler is reached through its kind's decorators. So a use case refuses with a
 * UseCaseRefused (ADR 0035), writes all or nothing, and nobody builds a handler by hand (ADR 0044). The refusals'
 * translation is outside the unit of work, so a refusal detected at commit time is translated too:
 * - A command handler is registered on the command bus. Messenger routes a message by the type of __invoke()'s
 *   argument. The decorators take any object, so the pass names the handled message explicitly, as it reads it on the
 *   handler itself.
 * - An event handler, a translator, is registered on the event bus as it is: it runs within the command that
 *   publishes, inside that command's decorators (ADR 0203).
 * - A query handler is injected as a QueryHandler named after its intent: `QueryHandler $consultOrganization` for
 *   `ConsultOrganizationHandler`.
 */
final class RefusalTranslatingHandlersPass implements CompilerPassInterface
{
    public const string COMMAND_HANDLER = 'app.command_handler';
    public const string QUERY_HANDLER = 'app.query_handler';
    public const string EVENT_HANDLER = 'app.event_handler';

    /** A command handler's decorators, from the innermost to the outermost, by their id's suffix. */
    private const array COMMAND_DECORATORS = ['transactional' => TransactionalCommandHandler::class, 'refusal_translating' => RefusalTranslatingCommandHandler::class];
    private const array QUERY_DECORATORS = ['refusal_translating' => RefusalTranslatingQueryHandler::class];

    public function process(ContainerBuilder $container): void
    {
        foreach (array_keys($container->findTaggedServiceIds(self::COMMAND_HANDLER)) as $id) {
            $container->getDefinition(self::decorate($container, $id, self::COMMAND_DECORATORS))
                ->addTag('messenger.message_handler', ['bus' => 'command.bus', 'handles' => $this->messageOf(self::classOf($container, $id))]);
        }

        foreach (array_keys($container->findTaggedServiceIds(self::EVENT_HANDLER)) as $id) {
            $container->getDefinition($id)
                ->addTag('messenger.message_handler', ['bus' => 'event.bus', 'handles' => $this->messageOf(self::classOf($container, $id))]);
        }

        foreach (array_keys($container->findTaggedServiceIds(self::QUERY_HANDLER)) as $id) {
            $container->registerAliasForArgument(self::decorate($container, $id, self::QUERY_DECORATORS), QueryHandler::class, $this->intentOf(self::classOf($container, $id)));
        }
    }

    /**
     * @param array<string, class-string> $decorators from the innermost to the outermost
     *
     * @return string the id of the outermost
     */
    private static function decorate(ContainerBuilder $container, string $id, array $decorators): string
    {
        $decorated = $id;

        foreach ($decorators as $suffix => $decorator) {
            $container->register($id.'.'.$suffix, $decorator)->setAutowired(true)->setArgument('$handler', new Reference($decorated));
            $decorated = $id.'.'.$suffix;
        }

        return $decorated;
    }

    public static function classOf(ContainerBuilder $container, string $id): string
    {
        return Typed::string($container->getParameterBag()->resolveValue($container->getDefinition($id)->getClass() ?? $id));
    }

    /**
     * `ConsultOrganizationHandler` has the intent `consultOrganization`.
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
