<?php

namespace App\Shared\Messaging;

use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;

/**
 * The bounded context a domain event happens in: its events live in its namespace, `Prospecter\Core\<Context>\Domain\Event`.
 */
final class EmittingContext
{
    public static function of(DomainEvent $event): string
    {
        return explode('\\', $event::class)[2];
    }
}
