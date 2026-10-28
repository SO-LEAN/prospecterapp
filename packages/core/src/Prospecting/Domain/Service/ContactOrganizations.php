<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Service;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownContact;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
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
    public function of(PersonId $contact, AccountId $account): array
    {
        $ids = array_map(static fn (Organization $organization): OrganizationId => $organization->id(), $this->naming($contact, $account));

        return [] === $ids ? throw UnknownContact::identifiedBy($contact) : $ids;
    }

    /**
     * @throws UnknownContact
     */
    public function ensureIsAContactOfTheAccount(PersonId $contact, AccountId $account): void
    {
        if (!$this->isAContactOfTheAccount($contact, $account)) {
            throw UnknownContact::identifiedBy($contact);
        }
    }

    public function isAContactOfTheAccount(PersonId $person, AccountId $account): bool
    {
        return [] !== $this->naming($person, $account);
    }

    /**
     * @return list<Organization>
     */
    private function naming(PersonId $contact, AccountId $account): array
    {
        return $this->organizations->withContacts([$contact], $account);
    }
}
