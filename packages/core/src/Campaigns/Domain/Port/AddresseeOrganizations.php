<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\Campaigns\Domain\Model\AddresseeOrganization;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Port: the organizations of the account that a contact works for (ADR 0089, 0144, 0223). The adapter translates
 * them from Prospecting's published service `NameOrganizationsOfContactsService`, in the order Prospecting gives them.
 */
interface AddresseeOrganizations
{
    /**
     * @return list<AddresseeOrganization> none for a contact that no organization of the account names
     */
    public function of(AccountId $accountId, ContactId $contact): array;
}
