<?php

namespace App\Shared\Messaging;

use Prospecter\Core\SharedKernel\Application\EventHandler;
use Psr\Container\ContainerInterface;

/**
 * The subscriptions of `config/subscriptions.json`, and their reactions through their decorators
 * (`App\Shared\DependencyInjection\SubscriptionsPass`).
 */
final readonly class Subscriptions
{
    /**
     * @param ContainerInterface                                                $reactions     the reaction of each subscription, by its name
     * @param array<string, array{sources: list<string>, events: list<string>}> $subscriptions by name, in the order of the file
     */
    public function __construct(private ContainerInterface $reactions, private array $subscriptions)
    {
    }

    /**
     * @return list<EventHandler>
     */
    public function reactingTo(TransportedEvent $event): array
    {
        $listening = array_filter(
            $this->subscriptions,
            static fn (array $subscription): bool => in_array($event->source, $subscription['sources'], true) && in_array($event->detailType, $subscription['events'], true),
        );

        return array_map($this->reactions->get(...), array_keys($listening));
    }
}
