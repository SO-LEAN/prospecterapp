<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact is never alone (ADR 0042): among the contacts an organization lets go, those no other organization
 * of the account names. A rule on the whole prospecting base, out of reach of one organization.
 */
final readonly class OrphanContacts
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    /**
     * @param list<PersonId> $contacts
     *
     * @return list<PersonId>
     */
    public function among(array $contacts, AccountId $accountId, OrganizationId $leaving): array
    {
        $stillNamed = array_filter($this->organizations->withContacts($contacts, $accountId), static fn (Organization $organization): bool => !$organization->id()->equals($leaving));

        return array_values(array_filter($contacts, static fn (PersonId $contact): bool => !array_any($stillNamed, static fn (Organization $organization): bool => $organization->hasContact($contact))));
    }
}
