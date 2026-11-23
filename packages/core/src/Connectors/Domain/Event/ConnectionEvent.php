<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Something that happened to a member's connection to Google, so far the only provider. The member is its subject,
 * because a member has one connection per provider.
 */
abstract readonly class ConnectionEvent extends DomainEvent
{
    public function subject(): MemberId
    {
        return $this->by;
    }
}
