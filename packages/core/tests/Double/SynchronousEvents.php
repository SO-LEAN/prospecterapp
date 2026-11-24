<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * Delivers the events the way the API delivers them. It finds each translator or policy by its __invoke()'s types, the
 * event's parent classes included. A translator runs at once, inside the command that publishes and its
 * decorators, like the API's event bus. A policy reacts once the command has succeeded, like the worker: each command
 * it returns is sent through its handler's decorators, in its own unit, in the order the policies subscribed. Then
 * the policies react to what those commands published, until nothing is left. A command that fails delivers nothing. A reaction that fails publishes
 * nothing, and its event joins the failures.
 */
final class SynchronousEvents implements Events
{
    /** @var list<array{class-string, EventHandler}> each translator with the type of event it takes */
    private array $translators = [];
    /** @var list<array{list<class-string>, \Closure(object): void}> each policy's reaction with the types of event it takes, in subscription order */
    private array $reactions = [];
    /** @var list<class-string<EventHandler|Policy>> */
    private array $subscribers = [];
    /** @var list<object> */
    private array $published = [];
    /** @var list<object> the events published but not delivered to the reactions yet */
    private array $undelivered = [];
    private bool $reactionsHeld = false;
    /** @var list<array{object, \Throwable}> each event a reaction failed on, with its failure */
    private array $failures = [];

    public function translateWith(EventHandler $translator): void
    {
        $this->translators[] = [$this->eventsOf($translator)[0], $translator];
        $this->subscribers[] = $translator::class;
    }

    /**
     * Subscribes a policy: each command it turns an event into is sent to its handler.
     *
     * @param \Closure(object): void $send
     */
    public function subscribePolicy(Policy $policy, \Closure $send): void
    {
        $this->reactions[] = [$this->eventsOf($policy), static function (object $event) use ($policy, $send): void {
            foreach (self::commandsOf($policy, $event) as $command) {
                $send($command);
            }
        }];
        $this->subscribers[] = $policy::class;
    }

    /**
     * @return list<class-string<EventHandler|Policy>>
     */
    public function subscribers(): array
    {
        return array_values(array_unique($this->subscribers));
    }

    /**
     * The events of this class published so far, oldest first.
     *
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return list<T>
     */
    public function published(string $class): array
    {
        return array_values(array_filter($this->published, static fn (object $event): bool => $event instanceof $class));
    }

    /**
     * The errors that the reactions to this class's events failed with, oldest first.
     *
     * @param class-string $class
     *
     * @return list<\Throwable>
     */
    public function failuresOf(string $class): array
    {
        return array_values(array_map(static fn (array $failed): \Throwable => $failed[1], array_filter($this->failures, static fn (array $failed): bool => $failed[0] instanceof $class)));
    }

    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            $this->published[] = $event;
            $this->undelivered[] = $event;

            foreach ($this->translators as [$type, $translator]) {
                if ($event instanceof $type) {
                    Invocable::call($translator, $event);
                }
            }
        }
    }

    /**
     * Runs the command, then delivers what it published to the reactions. A command that throws delivers nothing.
     *
     * @param \Closure(): void $command
     */
    public function deliverAfter(\Closure $command): void
    {
        $this->withoutWhatFails($command);

        if (!$this->reactionsHeld) {
            $this->deliverWhatWasPublished();
        }
    }

    /**
     * From now on, what the commands publish waits for its reactions, as it waits for the worker on the stack.
     */
    public function holdTheReactions(): void
    {
        $this->reactionsHeld = true;
    }

    private function deliverWhatWasPublished(): void
    {
        while ([] !== $this->undelivered) {
            $event = array_shift($this->undelivered);

            foreach ($this->reactionsTo($event) as $reaction) {
                $this->react($reaction, $event);
            }
        }
    }

    /**
     * @param \Closure(object): void $reaction
     */
    private function react(\Closure $reaction, object $event): void
    {
        try {
            $this->withoutWhatFails(static fn () => $reaction($event));
        } catch (\Throwable $failure) {
            $this->failures[] = [$event, $failure];
        }
    }

    /**
     * Keeps what a call publishes for the reactions only when the call succeeds.
     *
     * @param \Closure(): void $call
     */
    private function withoutWhatFails(\Closure $call): void
    {
        [$published, $undelivered] = [$this->published, $this->undelivered];

        try {
            $call();
        } catch (\Throwable $failure) {
            [$this->published, $this->undelivered] = [$published, $undelivered];

            throw $failure;
        }
    }

    /**
     * @return list<object> what a policy returns, as a list of commands
     */
    private static function commandsOf(Policy $policy, object $event): array
    {
        $returned = new \ReflectionMethod($policy, '__invoke')->invoke($policy, $event);

        $commands = match (true) {
            null === $returned => [],
            is_array($returned) => array_values($returned),
            default => [$returned],
        };

        return array_map(static fn (mixed $command): object => is_object($command) ? $command : throw new \LogicException(sprintf('%s returns something other than commands', $policy::class)), $commands);
    }

    /**
     * The types of event a handler or a policy takes: its __invoke()'s argument, a union naming several.
     *
     * @return non-empty-list<class-string>
     */
    private function eventsOf(EventHandler|Policy $reactor): array
    {
        $type = new \ReflectionMethod($reactor, '__invoke')->getParameters()[0]->getType();
        $types = $type instanceof \ReflectionUnionType ? $type->getTypes() : [$type];
        $events = array_values(array_filter(array_map(static fn (?\ReflectionType $type): ?string => $type instanceof \ReflectionNamedType && (class_exists($type->getName()) || interface_exists($type->getName())) ? $type->getName() : null, $types)));

        return [] !== $events && count($events) === count($types) ? $events : throw new \LogicException(sprintf('%s::__invoke() must take one event', $reactor::class));
    }

    /**
     * The reactions to the event's class or to one of its parents, in subscription order. An `OrganizationEvent`
     * handler receives every organization event.
     *
     * @return list<\Closure(object): void>
     */
    private function reactionsTo(object $event): array
    {
        return array_values(array_map(static fn (array $reaction): \Closure => $reaction[1], array_filter($this->reactions, static fn (array $reaction): bool => array_any($reaction[0], static fn (string $type): bool => $event instanceof $type))));
    }
}
