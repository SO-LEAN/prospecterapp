<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Port;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Port: whether the account prospects a person. PersonalData keeps the persons, and Prospecting never sees them. The
 * API answers through an anticorruption adapter (ADR 0041).
 */
interface KnownContacts
{
    public function isAContactOf(ContactId $person, AccountId $accountId): bool;
}
