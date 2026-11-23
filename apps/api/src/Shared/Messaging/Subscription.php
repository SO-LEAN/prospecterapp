<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

/**
 * A subscription from `config/subscriptions.json`, as the worker runs it: the context of its reaction. That context's
 * table keeps the subscription's inbox when it has one.
 */
final readonly class Subscription
{
    public function __construct(public string $name, public string $context, public bool $inbox)
    {
    }
}
