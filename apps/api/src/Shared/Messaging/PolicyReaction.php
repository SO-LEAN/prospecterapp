<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use Prospecter\Core\SharedKernel\Application\Handler\Policy;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * The one adapter of the policies (ADR 0203): it gives the event to the policy, then sends each command the policy
 * returns to the command bus, through the command handlers' decorators. A failure comes back as the handler threw it,
 * so that the worker sorts it: it applies a conflict again, keeps a refusal among the failures, and throws the rest.
 */
final readonly class PolicyReaction implements Reaction
{
    public function __construct(private Policy $policy, private MessageBusInterface $commandBus)
    {
    }

    public function __invoke(object $event): void
    {
        foreach ($this->commandsFor($event) as $command) {
            $this->send($command);
        }
    }

    /**
     * @return list<object> what the policy returns, as a list of commands
     */
    private function commandsFor(object $event): array
    {
        $commands = $this->policy->__invoke($event);

        return match (true) {
            null === $commands => [],
            is_array($commands) => $commands,
            default => [$commands],
        };
    }

    private function send(object $command): void
    {
        try {
            $this->commandBus->dispatch($command);
        } catch (HandlerFailedException $failure) {
            throw $failure->getPrevious() ?? $failure;
        }
    }
}
