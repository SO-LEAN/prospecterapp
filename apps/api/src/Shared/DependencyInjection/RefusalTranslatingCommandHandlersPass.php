<?php

namespace App\Shared\DependencyInjection;

use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers every command handler of the core on the command bus through a RefusalTranslatingHandler, so
 * that a command refuses with a UseCaseRefused (ADR 0035). Messenger routes a message by the type of the
 * argument of __invoke(): the decorator takes any object, so the command it handles is named explicitly.
 */
final class RefusalTranslatingCommandHandlersPass implements CompilerPassInterface
{
    public const string TAG = 'app.command_handler';

    public function process(ContainerBuilder $container): void
    {
        foreach (array_keys($container->findTaggedServiceIds(self::TAG)) as $id) {
            $container->register($id.'.refusal_translating', RefusalTranslatingHandler::class)
                ->setArguments([new Reference($id)])
                ->addTag('messenger.message_handler', ['bus' => 'command.bus', 'handles' => $this->commandOf($container->getParameterBag()->resolveValue($container->getDefinition($id)->getClass() ?? $id))]);
        }
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
