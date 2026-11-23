<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A member changed whether they are reminded of their planned exchanges, and how long before.
 */
final readonly class RemindersChanged extends DomainEvent
{
    public function subject(): MemberId
    {
        return $this->by;
    }
}
