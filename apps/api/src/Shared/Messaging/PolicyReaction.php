<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use App\Shared\CommandBus;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * The one adapter of the policies (ADR 0202): it gives the event to the policy, then sends each command the policy
 * returns to the command bus, through the command handlers' decorators.
 */
final readonly class PolicyReaction implements Reaction
{
    public function __construct(private Policy $policy, private CommandBus $commands)
    {
    }

    public function __invoke(object $event): void
    {
        foreach ($this->commandsFor($event) as $command) {
            $this->commands->dispatch($command);
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
}
