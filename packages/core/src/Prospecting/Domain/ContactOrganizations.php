<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownContact;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The organizations of the account of a prospector a person is a contact of: a rule on the whole prospecting
 * base, out of reach of one organization. A person none of them names is no contact of the account.
 */
final readonly class ContactOrganizations
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    /**
     * @return non-empty-list<OrganizationId>
     *
     * @throws UnknownContact
     */
    public function of(PersonId $contact, Prospector $prospector): array
    {
        $ids = array_map(static fn (Organization $organization): OrganizationId => $organization->id(), $this->naming($contact, $prospector));

        return [] === $ids ? throw UnknownContact::identifiedBy($contact) : $ids;
    }

    /**
     * @throws UnknownContact
     */
    public function ensureIsAContactOfTheAccount(PersonId $contact, Prospector $prospector): void
    {
        if ([] === $this->naming($contact, $prospector)) {
            throw UnknownContact::identifiedBy($contact);
        }
    }

    /**
     * @return list<Organization>
     */
    private function naming(PersonId $contact, Prospector $prospector): array
    {
        return $this->organizations->withContacts([$contact], $prospector->accountId);
    }
}
