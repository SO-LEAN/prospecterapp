<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Port;

use Prospecter\Core\Reporting\Domain\Model\ContactFact;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Port: the facts of the contacts of an account as they are now, translated from PersonalData, through its published
 * service `ListContactFactsService` (ADR 0045, 0089, 0099).
 */
interface PersonalDataFacts
{
    /**
     * @return iterable<ContactFact>
     */
    public function contactsOf(AccountId $accountId): iterable;
}
