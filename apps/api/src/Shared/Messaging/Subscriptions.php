<?php

namespace App\Shared\Messaging;

use Prospecter\Core\SharedKernel\Application\EventHandler;
use Psr\Container\ContainerInterface;

/**
 * The subscriptions of `config/subscriptions.json`, and their reactions through their decorators
 * (`App\Shared\DependencyInjection\SubscriptionsPass`).
 *
 * @phpstan-type Declared array{context: string, sources: list<string>, events: list<string>, inbox: bool}
 */
final readonly class Subscriptions
{
    /**
     * @param ContainerInterface      $reactions     the reaction of each subscription, by its name
     * @param array<string, Declared> $subscriptions by name, in the order of the file
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

    /**
     * @throws UnknownSubscription
     */
    public function named(string $name): Subscription
    {
        $declared = $this->subscriptions[$name] ?? throw UnknownSubscription::named($name);

        return new Subscription($name, $declared['context'], $declared['inbox']);
    }

    /**
     * Its reaction, through its decorators.
     */
    public function reactionOf(Subscription $subscription): EventHandler
    {
        return $this->reactions->get($subscription->name);
    }
}
