<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\SearchTerms;
use Prospecter\Core\SharedKernel\Domain\Slice;

final readonly class InMemoryOrganizationRepository implements OrganizationRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(OrganizationId $id): Organization
    {
        return $this->database->organization((string) $id) ?? throw UnknownOrganization::identifiedBy($id);
    }

    public function isEmailUsed(AccountId $accountId, Email $email, OrganizationId $except): bool
    {
        return array_any($this->prospectedBy($accountId), fn (Organization $organization): bool => !$organization->id()->equals($except) && $organization->contactDetails()->email?->value === $email->value);
    }

    public function save(Organization $organization): void
    {
        $this->database->storeOrganization($organization);
    }

    public function remove(Organization $organization): void
    {
        $this->database->forgetOrganization((string) $organization->id());
    }

    public function subsidiariesOf(OrganizationId $holding): array
    {
        return array_values(array_filter($this->database->organizations(), static fn (Organization $organization): bool => $organization->holdingId()?->equals($holding) ?? false));
    }

    public function withContacts(array $contacts, AccountId $accountId): array
    {
        return array_values(array_filter($this->prospectedBy($accountId), static fn (Organization $organization): bool => array_any($contacts, $organization->hasContact(...))));
    }

    public function sliceProspectedBy(AccountId $accountId, SearchTerms $search, ?string $after, int $max): Slice
    {
        return NewestFirst::slice($this->prospectedBy($accountId), static fn (Organization $organization): string => (string) $organization->id(), static fn (Organization $organization): SearchTerms => $organization->searchTerms(), $search, $after, $max);
    }

    /**
     * @return list<Organization>
     */
    private function prospectedBy(AccountId $accountId): array
    {
        return array_values(array_filter($this->database->organizations(), static fn (Organization $organization): bool => $organization->isProspectedBy($accountId)));
    }
}
