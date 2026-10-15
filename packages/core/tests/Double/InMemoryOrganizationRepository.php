<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\Organization\UnknownOrganization;
use Prospecter\Core\Domain\Shared\Page;

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

    public function countProspectedBy(OrganizationId $ownerId): int
    {
        return count($this->prospectedBy($ownerId));
    }

    public function pageProspectedBy(OrganizationId $ownerId, string $query, int $page, int $maxByPage): Page
    {
        $matching = array_values(array_filter($this->prospectedBy($ownerId), static fn (Organization $organization): bool => self::matches($organization, mb_strtolower($query))));
        usort($matching, static fn (Organization $a, Organization $b): int => strcmp((string) $b->id(), (string) $a->id()));
        $total = count($matching);

        return new Page($page, $total, intdiv($total - 1, $maxByPage) + 1, array_slice($matching, ($page - 1) * $maxByPage, $maxByPage));
    }

    /**
     * @return list<Organization>
     */
    private function prospectedBy(OrganizationId $ownerId): array
    {
        return array_values(array_filter($this->database->organizations(), static fn (Organization $organization): bool => $organization->isProspectedBy($ownerId)));
    }

    private static function matches(Organization $organization, string $query): bool
    {
        return '' === $query
            || str_contains(mb_strtolower((string) $organization->legalIdentity()->corporateName), $query)
            || str_contains(mb_strtolower((string) $organization->contactDetails()->email), $query);
    }
}
