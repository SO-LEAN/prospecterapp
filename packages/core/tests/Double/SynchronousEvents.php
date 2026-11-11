<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * The events delivered as the API delivers them, each handler found by the type of its __invoke(), a parent class of
 * the event included, and called through its decorators: a translator at once, within the command that publishes,
 * like the event bus of the API; a reaction once the command succeeded, like the worker, each in its own unit, in
 * the order they subscribed, then the reactions to what the reactions published, until none is left. A command
 * that fails delivers nothing; a reaction that fails publishes nothing, its event kept in the failures.
 */
final class SynchronousEvents implements Events
{
    /** @var list<array{class-string, EventHandler}> each translator with the type of event it takes */
    private array $translators = [];
    /** @var list<array{class-string, EventHandler}> each reaction with the type of event it takes, in the order they subscribed */
    private array $reactions = [];
    /** @var list<class-string<EventHandler>> */
    private array $subscribers = [];
    /** @var list<object> */
    private array $published = [];
    /** @var list<object> published, not delivered to the reactions yet */
    private array $undelivered = [];
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
     * The events of this class published so far, the oldest first.
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
     * What the reactions to the events of this class failed with, the oldest first.
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
                $translator($event);
            }
        }
    }

    /**
     * Runs the command, then delivers what it published to the reactions; a command that throws delivers nothing.
     *
     * @param \Closure(): void $command
     */
    public function deliverAfter(\Closure $command): void
    {
        $this->withoutWhatFails($command);
        $this->deliverWhatWasPublished();
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
            $this->withoutWhatFails(static fn () => $reaction($event));
        } catch (\Throwable $failure) {
            $this->failures[] = [$event, $failure];
        }
    }

    /**
     * What a call publishes is kept for the reactions only when it succeeds.
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

        return $type instanceof \ReflectionNamedType ? $type->getName() : throw new \LogicException(sprintf('%s::__invoke() must take one event', $handler::class));
    }

    /**
     * The handlers taking its class or a parent of it, in the order they subscribed: a handler of
     * `OrganizationEvent` receives every event of an organization.
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
