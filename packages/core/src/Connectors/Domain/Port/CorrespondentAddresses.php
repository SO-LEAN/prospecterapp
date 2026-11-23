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
 * Port: the address of one of the account's correspondents (ADR 0089). It is translated from the contexts that know
 * the correspondents, through their published services: PersonalData for the contacts, Prospecting for the
 * organizations. The address is read to search the mailbox, and is never kept.
 */
interface CorrespondentAddresses
{
    /**
     * A contact's address as the member reads it. None when the contact has no address.
     *
     * @throws UnknownContact unless the member reads the contact unmasked
     */
    public function ofContact(MemberId $reader, PersonId $contact): ?Email;

    /**
     * The address of an organization that the account prospects. None when the organization has no address.
     *
     * @throws UnknownOrganization unless the account prospects it
     */
    public function ofOrganization(AccountId $accountId, OrganizationId $organization): ?Email;
}
