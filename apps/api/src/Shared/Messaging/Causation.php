<?php

namespace App\Shared\Messaging;

use App\Shared\Identities;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The cause of what is published now: the command being handled, or the event a reaction is reacting to.
 */
final class Causation implements ResetInterface
{
    /** @var list<Cause> from the command to the innermost reaction */
    private array $causes = [];

    public function __construct(private readonly Identities $identities)
    {
    }

    /**
     * Outside a command, what is published is a command of its own.
     */
    public function current(): Cause
    {
        return array_last($this->causes) ?? Cause::ofACommand($this->identities->next());
    }

    /**
     * @template T
     *
     * @param \Closure(): T $command
     *
     * @return T
     */
    public function withinACommand(\Closure $command): mixed
    {
        return [] === $this->causes ? $this->within(Cause::ofACommand($this->identities->next()), $command) : $command();
    }

    /**
     * @template T
     *
     * @param \Closure(): T $work
     *
     * @return T
     */
    public function within(Cause $cause, \Closure $work): mixed
    {
        $this->causes[] = $cause;

        try {
            return $work();
        } finally {
            array_pop($this->causes);
        }
    }

    public function reset(): void
    {
        $this->causes = [];
    }
}
