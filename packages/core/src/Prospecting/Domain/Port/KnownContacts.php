<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Port: whether a person is one the account prospects. PersonalData keeps the persons, Prospecting never sees
 * it; the API answers through an anticorruption adapter (ADR 0041).
 */
interface KnownContacts
{
    public function isAContactOf(PersonId $person, AccountId $accountId): bool;
}
