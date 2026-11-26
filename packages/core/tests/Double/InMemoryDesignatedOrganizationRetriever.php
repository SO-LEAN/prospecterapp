<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\Port\DesignatedOrganizationRetriever;
use Prospecter\Core\Prospecting\Contract\Service\IdentifyOrganizations\CandidateResponse;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Company\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\Company\Siret;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReference;

/**
 * Reads the organizations that the in-memory repository saved, by the keys that designate them in their account.
 * The adapter reads the reservations that the repository writes in the same way.
 */
final readonly class InMemoryDesignatedOrganizationRetriever implements DesignatedOrganizationRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function withSiret(AccountId $accountId, Siret $siret): ?OrganizationId
    {
        return $this->firstOf($accountId, static fn (Organization $organization): bool => $organization->siret()?->value === $siret->value);
    }

    public function withReference(AccountId $accountId, ExternalReference $reference): ?OrganizationId
    {
        return $this->firstOf($accountId, static fn (Organization $organization): bool => $organization->references()->holds($reference));
    }

    public function withWebsiteDomain(AccountId $accountId, string $domain): ?OrganizationId
    {
        return $this->firstOf($accountId, static fn (Organization $organization): bool => $organization->website()?->domain() === $domain);
    }

    public function withEmail(AccountId $accountId, Email $email): ?OrganizationId
    {
        return $this->firstOf($accountId, static fn (Organization $organization): bool => $organization->contactDetails()->email?->value === $email->value);
    }

    /**
     * @param list<OrganizationId> $ids
     */
    public function named(array $ids): array
    {
        $organizations = array_filter(array_map(fn (OrganizationId $id): ?Organization => $this->database->organization($id->value), $ids));

        return array_values(array_map(self::candidate(...), $organizations));
    }

    /**
     * @param callable(Organization): bool $designated
     */
    private function firstOf(AccountId $accountId, callable $designated): ?OrganizationId
    {
        return array_find($this->database->organizations(), static fn (Organization $organization): bool => $organization->isProspectedBy($accountId) && $designated($organization))?->id();
    }

    private static function candidate(Organization $organization): CandidateResponse
    {
        $name = StateOf::the($organization)->object('legalIdentity', LegalIdentity::class)->fullName();

        return new CandidateResponse($organization->id()->value, '' === $name ? (string) $organization->contactDetails()->email : $name);
    }
}
