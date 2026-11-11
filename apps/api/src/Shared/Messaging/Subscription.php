<?php

namespace App\Shared\Messaging;

/**
 * A subscription of `config/subscriptions.json` as the worker runs it: the context of its reaction, whose table
 * keeps its inbox when it has one.
 */
final readonly class Subscription
{
    public function __construct(public string $name, public string $context, public bool $inbox)
    {
    }
}
