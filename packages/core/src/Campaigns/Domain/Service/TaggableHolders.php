<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Service;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownContact;
use Prospecter\Core\Campaigns\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\HolderKind;
use Prospecter\Core\Campaigns\Domain\Port\TaggableContacts;
use Prospecter\Core\Campaigns\Domain\Port\TaggableOrganizations;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Domain service (ADR 0039): an account tags only the organizations it prospects and its contacts, as Prospecting and
 * PersonalData know them. Anything else is unknown to the account.
 */
final readonly class TaggableHolders
{
    public function __construct(
        private TaggableOrganizations $organizations,
        private TaggableContacts $contacts,
    ) {
    }

    /**
     * @throws UnknownOrganization
     * @throws UnknownContact
     */
    public function ensureKnownBy(AccountId $accountId, Holder $holder): void
    {
        match ($holder->kind) {
            HolderKind::Organization => $this->organizations->isProspectedBy(new OrganizationId($holder->id->value), $accountId) ?: throw UnknownOrganization::identifiedBy($holder->id),
            HolderKind::Contact => $this->ensureContactsOf($accountId, [new PersonId($holder->id->value)]),
        };
    }

    /**
     * @param list<PersonId> $persons
     *
     * @throws UnknownContact for the first person who is not a contact of the account
     */
    public function ensureContactsOf(AccountId $accountId, array $persons): void
    {
        $contacts = $this->contacts->contactsAmong($persons, $accountId);
        $unknown = array_find($persons, static fn (PersonId $person): bool => !array_any($contacts, $person->equals(...)));

        if (null !== $unknown) {
            throw UnknownContact::identifiedBy($unknown);
        }
    }
}
