<?php

declare(strict_types=1);

namespace App\Campaigns\AntiCorruption;

use Prospecter\Core\Campaigns\Domain\Model\AddresseeOrganization;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Port\AddresseeOrganizations;
use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\KeptOrganizationsOfContacts;
use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\NameOrganizationsOfContacts;
use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\NameOrganizationsOfContactsService;
use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\OrganizationOfContactResponse;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Anticorruption layer (ADR 0045, 0089, 0144, 0223). Campaigns learns from Prospecting the organizations a contact
 * works for, and when the contact was attached to each. It asks about a set of one.
 */
final readonly class ProspectingAddresseeOrganizations implements AddresseeOrganizations
{
    public function __construct(private NameOrganizationsOfContactsService $nameOrganizationsOfContactsService)
    {
    }

    public function of(AccountId $accountId, ContactId $contact): array
    {
        $kept = new KeptOrganizationsOfContacts();
        ($this->nameOrganizationsOfContactsService)(new NameOrganizationsOfContacts($accountId->value, [$contact->value]), $kept);

        return array_map(static fn (OrganizationOfContactResponse $organization): AddresseeOrganization => new AddresseeOrganization($organization->fullName, $organization->attachedAt), $kept->contacts[0]->organizations ?? []);
    }
}
