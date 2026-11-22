<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\ConsultOrganizationsOfContacts\OrganizationOfContact;
use Prospecter\Core\Prospecting\Application\Port\OrganizationOfContactRetriever;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The DynamoDB retriever in the scenarios: the organizations of the account naming each contact, in the order of
 * their identities as the links of the table give them, read without their getters.
 */
final readonly class InMemoryOrganizationOfContactRetriever implements OrganizationOfContactRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function ofContacts(array $contacts, AccountId $accountId): array
    {
        $organizations = array_filter($this->database->organizations(), static fn (Organization $organization): bool => $organization->isProspectedBy($accountId));
        usort($organizations, static fn (Organization $one, Organization $other): int => strcmp($one->id()->value, $other->id()->value));
        $ofContacts = [];

        foreach ($contacts as $contact) {
            $naming = array_filter($organizations, static fn (Organization $organization): bool => self::names($organization, $contact));
            $ofContacts[$contact->value] = array_values(array_map(self::organizationOfContact(...), $naming));
        }

        return $ofContacts;
    }

    private static function names(Organization $organization, PersonId $contact): bool
    {
        return array_any(StateOf::the($organization)->listOf('contacts', PersonId::class), $contact->equals(...));
    }

    private static function organizationOfContact(Organization $organization): OrganizationOfContact
    {
        return new OrganizationOfContact($organization->id()->value, StateOf::the($organization)->object('legalIdentity', LegalIdentity::class)->fullName());
    }
}
