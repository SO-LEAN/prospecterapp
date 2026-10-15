<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;

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
