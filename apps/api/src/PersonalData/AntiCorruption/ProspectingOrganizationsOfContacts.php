<?php

declare(strict_types=1);

namespace App\PersonalData\AntiCorruption;

use Prospecter\Core\PersonalData\Domain\Model\OrganizationId;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Port\OrganizationsOfContacts;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsNameTheseContacts\KeptOrganizationsNamingContacts;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsNameTheseContacts\OrganizationsNamingContactResponse;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsNameTheseContacts\WhichOrganizationsNameTheseContacts;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsNameTheseContacts\WhichOrganizationsNameTheseContactsService;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Anticorruption layer (ADR 0045, 0089). PersonalData compares a contact with the other contacts of their
 * organizations. Prospecting tells which organizations name the contacts, in one call for all of them.
 */
final readonly class ProspectingOrganizationsOfContacts implements OrganizationsOfContacts
{
    public function __construct(private WhichOrganizationsNameTheseContactsService $whichOrganizationsNameTheseContactsService)
    {
    }

    /**
     * @param list<PersonId> $contacts
     */
    public function organizationsOf(array $contacts, AccountId $accountId): array
    {
        $kept = new KeptOrganizationsNamingContacts();
        ($this->whichOrganizationsNameTheseContactsService)(new WhichOrganizationsNameTheseContacts($accountId->value, array_map(static fn (PersonId $contact): string => $contact->value, $contacts)), $kept);

        return array_combine(
            array_map(static fn (OrganizationsNamingContactResponse $contact): string => $contact->personId, $kept->contacts),
            array_map(static fn (OrganizationsNamingContactResponse $contact): array => array_map(static fn (string $id): OrganizationId => new OrganizationId($id), $contact->organizationIds), $kept->contacts),
        );
    }
}
