<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A member changed whether and how long before a planned exchange they are reminded of it.
 */
final readonly class RemindersChanged extends DomainEvent
{
    public function subject(): MemberId
    {
        return $this->by;
    }
}
