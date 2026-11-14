<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Something that happened to the connection of a member to Google, the one provider so far: the member is its
 * subject, a member having one connection per provider.
 */
abstract readonly class ConnectionEvent extends DomainEvent
{
    public function subject(): MemberId
    {
        return $this->by;
    }
}
