<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\Campaigns\Domain\Model\OrganizationFact;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Port: the organizations an account prospects now and the contacts each one names (ADR 0045, 0089, 0141). It reads the
 * facts that Prospecting gives again through its published service `ListOrganizationFactsService`.
 */
interface OrganizationsAndTheirContacts
{
    /**
     * @return iterable<OrganizationFact>
     */
    public function of(AccountId $accountId): iterable;
}
