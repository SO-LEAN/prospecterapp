<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * Delivers the events the way the API delivers them. It finds each handler by its __invoke()'s type, the event's
 * parent classes included, and calls it through its decorators. A translator runs at once, inside the command that
 * publishes, like the API's event bus. A reaction runs once the command has succeeded, like the worker. Each
 * reaction runs in its own unit, in the order they subscribed. Then the reactions to what the reactions published
 * run, until none is left. A command that fails delivers nothing. A reaction that fails publishes nothing, and its
 * event joins the failures.
 */
final class SynchronousEvents implements Events
{
    /** @var list<array{class-string, EventHandler}> each translator with the type of event it takes */
    private array $translators = [];
    /** @var list<array{class-string, EventHandler}> each reaction with the type of event it takes, in subscription order */
    private array $reactions = [];
    /** @var list<class-string<EventHandler>> */
    private array $subscribers = [];
    /** @var list<object> */
    private array $published = [];
    /** @var list<object> the events published but not delivered to the reactions yet */
    private array $undelivered = [];
    private bool $reactionsHeld = false;
    /** @var list<array{object, \Throwable}> each event a reaction failed on, with its failure */
    private array $failures = [];

    public function translateWith(EventHandler $translator, EventHandler $calledThrough): void
    {
        $this->translators[] = [$this->eventOf($translator), $calledThrough];
        $this->subscribers[] = $translator::class;
    }

    public function subscribe(EventHandler $reaction, EventHandler $calledThrough): void
    {
        $this->reactions[] = [$this->eventOf($reaction), $calledThrough];
        $this->subscribers[] = $reaction::class;
    }

    /**
     * @return list<class-string<EventHandler>>
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

            foreach ($this->handlersOf($event, $this->translators) as $translator) {
                Invocable::call($translator, $event);
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

            foreach ($this->handlersOf($event, $this->reactions) as $reaction) {
                $this->react($reaction, $event);
            }
        }
    }

    private function react(EventHandler $reaction, object $event): void
    {
        try {
            $this->withoutWhatFails(static fn () => Invocable::call($reaction, $event));
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
     * @return class-string
     */
    private function eventOf(EventHandler $handler): string
    {
        $type = new \ReflectionMethod($handler, '__invoke')->getParameters()[0]->getType();

        return $type instanceof \ReflectionNamedType && (class_exists($type->getName()) || interface_exists($type->getName())) ? $type->getName() : throw new \LogicException(sprintf('%s::__invoke() must take one event', $handler::class));
    }

    /**
     * The handlers that take the event's class or one of its parents, in subscription order. An `OrganizationEvent`
     * handler receives every organization event.
     *
     * @param list<array{class-string, EventHandler}> $subscribed
     *
     * @return list<EventHandler>
     */
    private function handlersOf(object $event, array $subscribed): array
    {
        return array_values(array_map(static fn (array $handler): EventHandler => $handler[1], array_filter($subscribed, static fn (array $handler): bool => $event instanceof $handler[0])));
    }
}
