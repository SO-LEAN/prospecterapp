<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\Connectors\Domain\Exception\UnknownContact;
use Prospecter\Core\Connectors\Domain\Exception\UnknownOrganization;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Port: the address a correspondent of the account has, translated from the contexts that know them, PersonalData
 * for the contacts and Prospecting for the organizations, through their published services (ADR 0089): read to search
 * the mailbox, never kept.
 */
interface CorrespondentAddresses
{
    /**
     * The address of a contact as the member reads them, none when they have none.
     *
     * @throws UnknownContact unless the member reads them in clear
     */
    public function ofContact(MemberId $reader, PersonId $contact): ?Email;

    /**
     * The address of an organization the account prospects, none when it has none.
     *
     * @throws UnknownOrganization unless the account prospects it
     */
    public function ofOrganization(AccountId $accountId, OrganizationId $organization): ?Email;
}
