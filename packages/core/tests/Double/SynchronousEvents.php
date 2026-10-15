<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * The events delivered at once to the real handlers that subscribed, found by the type of their __invoke(),
 * like the synchronous event bus of the API.
 */
final class SynchronousEvents implements Events
{
    /** @var array<class-string, list<EventHandler>> */
    private array $handlers = [];

    public function subscribe(EventHandler $handler): void
    {
        $type = new \ReflectionMethod($handler, '__invoke')->getParameters()[0]->getType();

        if (!$type instanceof \ReflectionNamedType) {
            throw new \LogicException(sprintf('%s::__invoke() must take one event', $handler::class));
        }

        $this->handlers[$type->getName()][] = $handler;
    }

    /**
     * @return list<class-string<EventHandler>>
     */
    public function subscribers(): array
    {
        return array_values(array_unique(array_merge(...array_values(array_map(static fn (array $handlers): array => array_map(static fn (EventHandler $handler): string => $handler::class, $handlers), $this->handlers)))));
    }

    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            foreach ($this->handlers[$event::class] ?? [] as $handler) {
                $handler($event);
            }
        }
    }
}
