<?php

namespace App\Shared\Messaging;

/**
 * The worker was given a subscription `config/subscriptions.json` does not name, or an event it does not listen to,
 * the rule of an older or newer version: nothing reacts.
 */
final class UnknownSubscription extends \RuntimeException
{
    public static function named(string $name): self
    {
        return new self(sprintf('No subscription %s is declared in config/subscriptions.json', $name));
    }

    public static function notListeningTo(string $name, string $detailType): self
    {
        return new self(sprintf('The subscription %s of config/subscriptions.json does not listen to %s', $name, $detailType));
    }
}
