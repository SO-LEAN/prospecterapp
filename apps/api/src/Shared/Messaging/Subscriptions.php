<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use Prospecter\Core\SharedKernel\Application\EventHandler;
use Psr\Container\ContainerInterface;

/**
 * The subscriptions of `config/subscriptions.json`, and their reactions to each event they listen to, through their
 * decorators (`App\Shared\DependencyInjection\SubscriptionsPass`); a subscription naming the payloads it listens to
 * reacts to those only, as its rule of the bus passes them (`PayloadPattern`, ADR 0127).
 *
 * @phpstan-type Declared array{context: string, sources: list<string>, events: list<string>, payload?: array<string, mixed>, inbox: bool}
 */
final readonly class Subscriptions
{
    /**
     * @param ContainerInterface      $reactions     the reaction of each subscription to each event, by reactionKey()
     * @param array<string, Declared> $subscriptions by name, in the order of the file
     */
    public function __construct(private ContainerInterface $reactions, private array $subscriptions)
    {
    }

    /**
     * The key of the reaction of a subscription to an event, by its detail-type.
     */
    public static function reactionKey(string $subscription, string $detailType): string
    {
        return $subscription.' '.$detailType;
    }

    /**
     * @return list<EventHandler>
     */
    public function reactingTo(TransportedEvent $event): array
    {
        $listening = array_filter(
            $this->subscriptions,
            static fn (array $subscription): bool => in_array($event->source, $subscription['sources'], true) && in_array($event->detailType, $subscription['events'], true) && PayloadPattern::matches($event->payload, $subscription['payload'] ?? []),
        );

        return array_map(fn (string $name): EventHandler => $this->reaction(self::reactionKey($name, $event->detailType)), array_keys($listening));
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
     * Its reaction to the event, through its decorators.
     *
     * @throws UnknownSubscription when it does not listen to the event
     */
    public function reactionOf(Subscription $subscription, TransportedEvent $event): EventHandler
    {
        $key = self::reactionKey($subscription->name, $event->detailType);

        return $this->reactions->has($key) ? $this->reaction($key) : throw UnknownSubscription::notListeningTo($subscription->name, $event->detailType);
    }

    private function reaction(string $key): EventHandler
    {
        $reaction = $this->reactions->get($key);

        return $reaction instanceof EventHandler ? $reaction : throw new \LogicException(sprintf('The reaction %s is no event handler', $key));
    }
}
