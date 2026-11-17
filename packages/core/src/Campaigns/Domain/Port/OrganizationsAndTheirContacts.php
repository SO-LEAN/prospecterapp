<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\Campaigns\Domain\Model\OrganizationFact;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Port: the organizations an account prospects and the contacts each names, as they are now, from the facts
 * Prospecting gives again through its published service `ListOrganizationFactsService` (ADR 0045, 0089, 0141).
 */
interface OrganizationsAndTheirContacts
{
    /**
     * @return iterable<OrganizationFact>
     */
    public function of(AccountId $accountId): iterable;
}
