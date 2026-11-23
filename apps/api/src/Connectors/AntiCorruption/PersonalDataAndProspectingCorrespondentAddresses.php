<?php

declare(strict_types=1);

namespace App\Connectors\AntiCorruption;

use Prospecter\Core\Connectors\Domain\Exception\UnknownContact;
use Prospecter\Core\Connectors\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Connectors\Domain\Port\CorrespondentAddresses;
use Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts\KeptEmailsOfContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts\WhichEmailsHaveTheseContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts\WhichEmailsHaveTheseContactsService;
use Prospecter\Core\Prospecting\Contract\Service\WhichEmailsHaveTheseOrganizations\KeptEmailsOfOrganizations;
use Prospecter\Core\Prospecting\Contract\Service\WhichEmailsHaveTheseOrganizations\WhichEmailsHaveTheseOrganizations;
use Prospecter\Core\Prospecting\Contract\Service\WhichEmailsHaveTheseOrganizations\WhichEmailsHaveTheseOrganizationsService;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Anticorruption layer (ADR 0045, 0089). Connectors gets a contact's address from PersonalData and an organization's
 * address from Prospecting. Each context is asked about a set of one. Connectors searches the mailbox by the address
 * and never keeps it.
 */
final readonly class PersonalDataAndProspectingCorrespondentAddresses implements CorrespondentAddresses
{
    public function __construct(
        private WhichEmailsHaveTheseContactsService $whichEmailsHaveTheseContactsService,
        private WhichEmailsHaveTheseOrganizationsService $whichEmailsHaveTheseOrganizationsService,
    ) {
    }

    public function ofContact(MemberId $reader, PersonId $contact): ?Email
    {
        $kept = new KeptEmailsOfContacts();
        ($this->whichEmailsHaveTheseContactsService)(new WhichEmailsHaveTheseContacts($reader->value, [$contact->value]), $kept);

        if (!array_key_exists($contact->value, $kept->emails)) {
            throw UnknownContact::withId($contact);
        }

        return self::emailOf($kept->emails[$contact->value]);
    }

    public function ofOrganization(AccountId $accountId, OrganizationId $organization): ?Email
    {
        $kept = new KeptEmailsOfOrganizations();
        ($this->whichEmailsHaveTheseOrganizationsService)(new WhichEmailsHaveTheseOrganizations([$organization->value], $accountId->value), $kept);

        if (!array_key_exists($organization->value, $kept->emails)) {
            throw UnknownOrganization::withId($organization);
        }

        return self::emailOf($kept->emails[$organization->value]);
    }

    private static function emailOf(?string $address): ?Email
    {
        return null === $address ? null : new Email($address);
    }
}
