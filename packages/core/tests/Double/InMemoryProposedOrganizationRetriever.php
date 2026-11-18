<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\Port\ProposedOrganizationRetriever;
use Prospecter\Core\Prospecting\Application\ProposeOrganizationToAttach\ProposedOrganization;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Reads the organizations the in-memory repository saved by the domain of their email, as the adapter reads the
 * links the repository writes.
 */
final readonly class InMemoryProposedOrganizationRetriever implements ProposedOrganizationRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function firstWithEmailDomain(AccountId $accountId, string $domain): ?ProposedOrganization
    {
        $withTheDomain = array_filter($this->database->organizations(), static fn (Organization $organization): bool => $organization->isProspectedBy($accountId) && self::hasTheDomain($organization, $domain));
        usort($withTheDomain, static fn (Organization $one, Organization $other): int => (string) $one->id() <=> (string) $other->id());

        return [] === $withTheDomain ? null : self::proposed($withTheDomain[0]);
    }

    private static function hasTheDomain(Organization $organization, string $domain): bool
    {
        $email = $organization->contactDetails()->email;

        return null !== $email && $email->domain() === $domain;
    }

    private static function proposed(Organization $organization): ProposedOrganization
    {
        $name = StateOf::the($organization)['legalIdentity']->fullName();

        return new ProposedOrganization($organization->id()->value, '' === $name ? (string) $organization->contactDetails()->email : $name);
    }
}
