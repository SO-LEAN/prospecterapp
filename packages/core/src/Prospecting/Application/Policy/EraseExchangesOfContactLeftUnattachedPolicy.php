<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\Prospecting\Application\Exchange\EraseExchangesOfContactLeftUnattached\EraseExchangesOfContactLeftUnattached;
use Prospecter\Core\Prospecting\Domain\Event\ContactLeftUnattached;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When nobody attached a contact in time, the exchanges kept for no organization go with them (ADR 0146).
 */
final readonly class EraseExchangesOfContactLeftUnattachedPolicy implements Policy
{
    public function __invoke(ContactLeftUnattached $event): EraseExchangesOfContactLeftUnattached
    {
        return new EraseExchangesOfContactLeftUnattached(
            accountId: $event->accountId->value,
            holderId: $event->by->value,
            contactId: $event->contact->value,
            executedAt: $event->occurredAt,
        );
    }
}
