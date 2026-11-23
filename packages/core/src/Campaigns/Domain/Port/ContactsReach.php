<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\Campaigns\Domain\Model\ContactFact;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Port: an account's contacts and how each is reached now (ADR 0045, 0089, 0141). It reads the facts that PersonalData
 * gives again through its published service `ListContactFactsService`.
 */
interface ContactsReach
{
    /**
     * @return iterable<ContactFact>
     */
    public function of(AccountId $accountId): iterable;
}
