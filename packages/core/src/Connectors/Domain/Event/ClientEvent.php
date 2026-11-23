<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;

/**
 * Something that happened to an account's client at a provider. The account is its subject, because an account has
 * one client per provider.
 */
abstract readonly class ClientEvent extends DomainEvent
{
    public function subject(): AccountId
    {
        return $this->accountId;
    }
}
