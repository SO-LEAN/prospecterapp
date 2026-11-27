<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\Connectors\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Connectors\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Port: the address of an organization the account prospects (ADR 0089, 0223). It is translated from Prospecting's
 * published service `WhichEmailsHaveTheseOrganizationsService`. The address is read to search the mailbox, and is
 * never kept.
 */
interface OrganizationAddresses
{
    /**
     * None when the organization has no address.
     *
     * @throws UnknownOrganization unless the account prospects it
     */
    public function of(AccountId $accountId, OrganizationId $organization): ?Email;
}
