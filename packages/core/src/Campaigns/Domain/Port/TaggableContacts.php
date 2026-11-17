<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Port: whether a person is a contact of an account, asked of PersonalData through its published service
 * `WhichAreContactsService` (ADR 0045, 0089).
 */
interface TaggableContacts
{
    public function isAContactOf(PersonId $person, AccountId $accountId): bool;
}
