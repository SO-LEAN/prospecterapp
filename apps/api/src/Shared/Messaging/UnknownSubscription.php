<?php

namespace App\Shared\Messaging;

/**
 * The worker was given a subscription `config/subscriptions.json` does not name, the rule of an older or newer
 * version: nothing reacts.
 */
final class UnknownSubscription extends \RuntimeException
{
    public static function named(string $name): self
    {
        return new self(sprintf('No subscription %s is declared in config/subscriptions.json', $name));
    }
}
