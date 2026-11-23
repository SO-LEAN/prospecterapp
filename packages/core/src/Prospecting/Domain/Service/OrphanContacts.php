<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Service;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * A contact is never alone (ADR 0042). Among the contacts an organization lets go, this finds those that no other
 * organization of the account names. The rule covers the whole prospecting base, out of one organization's reach.
 */
final readonly class OrphanContacts
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    /**
     * @param list<ContactId> $contacts
     *
     * @return list<ContactId>
     */
    public function among(array $contacts, AccountId $accountId, OrganizationId $leaving): array
    {
        $stillNamed = array_filter($this->organizations->withContacts($contacts, $accountId), static fn (Organization $organization): bool => !$organization->id()->equals($leaving));

        return array_values(array_filter($contacts, static fn (ContactId $contact): bool => !array_any($stillNamed, static fn (Organization $organization): bool => $organization->hasContact($contact))));
    }
}
