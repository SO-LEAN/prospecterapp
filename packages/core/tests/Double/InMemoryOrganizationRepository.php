<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\UnknownOrganization;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Page;

final readonly class InMemoryOrganizationRepository implements OrganizationRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function nextIdentity(): OrganizationId
    {
        return new OrganizationId($this->database->uuids->next());
    }

    public function get(OrganizationId $id): Organization
    {
        return $this->database->organization((string) $id) ?? throw new UnknownOrganization($id);
    }

    public function save(Organization $organization): void
    {
        $this->database->storeOrganization($organization);
    }

    public function pageProspectedBy(AccountId $accountId, string $query, int $page, int $maxByPage): Page
    {
        $matching = array_values(array_filter($this->prospectedBy($accountId), static fn (Organization $organization): bool => self::matches($organization, mb_strtolower($query))));
        usort($matching, static fn (Organization $a, Organization $b): int => strcmp((string) $b->id(), (string) $a->id()));
        $total = count($matching);

        return new Page($page, $total, intdiv($total - 1, $maxByPage) + 1, array_slice($matching, ($page - 1) * $maxByPage, $maxByPage));
    }

    /**
     * @return list<Organization>
     */
    private function prospectedBy(AccountId $accountId): array
    {
        return array_values(array_filter($this->database->organizations(), static fn (Organization $organization): bool => $organization->isProspectedBy($accountId)));
    }

    private static function matches(Organization $organization, string $query): bool
    {
        return '' === $query
            || str_contains(mb_strtolower((string) $organization->legalIdentity()->corporateName), $query)
            || str_contains(mb_strtolower((string) $organization->contactDetails()->email), $query);
    }
}
