<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownContact;
use Prospecter\Core\Campaigns\Domain\Model\Addressee;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Port: what a message calls a contact (ADR 0089, 0144). It translates what the contexts that know the contact publish
 * through their services. PersonalData gives their name and job title as the member reads them (`NameContactsService`).
 * Prospecting gives their first organization (`NameOrganizationsOfContactsService`).
 */
interface Addressees
{
    /**
     * @throws UnknownContact when the member reads no such contact in their account
     */
    public function addressee(AccountId $accountId, MemberId $reader, PersonId $contact): Addressee;
}
