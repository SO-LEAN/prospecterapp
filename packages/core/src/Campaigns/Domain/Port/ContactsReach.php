<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\Campaigns\Domain\Model\ContactFact;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Port: the contacts of an account and how each is reached, as they are now, from the facts PersonalData gives again
 * through its published service `ListContactFactsService` (ADR 0045, 0089, 0141).
 */
interface ContactsReach
{
    /**
     * @return iterable<ContactFact>
     */
    public function of(AccountId $accountId): iterable;
}
