<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Something that happened to an account.
 */
abstract readonly class AccountEvent extends DomainEvent
{
    public function subject(): AccountId
    {
        return $this->accountId;
    }
}
