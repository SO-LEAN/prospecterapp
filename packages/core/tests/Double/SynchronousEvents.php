<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * The events delivered at once to the real handlers that subscribed, found by the type of their __invoke(),
 * like the synchronous event bus of the API, each called through its decorators.
 */
final class SynchronousEvents implements Events
{
    /** @var array<class-string, list<EventHandler>> */
    private array $handlers = [];
    /** @var list<class-string<EventHandler>> */
    private array $subscribers = [];
    /** @var list<object> */
    private array $published = [];

    public function subscribe(EventHandler $handler, EventHandler $calledThrough): void
    {
        $type = new \ReflectionMethod($handler, '__invoke')->getParameters()[0]->getType();

        if (!$type instanceof \ReflectionNamedType) {
            throw new \LogicException(sprintf('%s::__invoke() must take one event', $handler::class));
        }

        $this->handlers[$type->getName()][] = $calledThrough;
        $this->subscribers[] = $handler::class;
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

    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            $this->published[] = $event;

            foreach ($this->handlers[$event::class] ?? [] as $handler) {
                $handler($event);
            }
        }
    }
}
