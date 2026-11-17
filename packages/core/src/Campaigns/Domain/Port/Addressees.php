<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Port;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownContact;
use Prospecter\Core\Campaigns\Domain\Model\Addressee;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Port: what a message names a contact by, translated from the contexts that know it, through their published
 * services: their name and their job title as the member reads them (PersonalData, `NameContactsService`), their
 * first organization (Prospecting, `NameOrganizationsOfContactsService`), ADR 0089, 0144.
 */
interface Addressees
{
    /**
     * @throws UnknownContact when the member reads no such contact of their account
     */
    public function addressee(AccountId $accountId, MemberId $reader, PersonId $contact): Addressee;
}
