<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\Port\FoundOrganizationRetriever;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\FoundOrganization;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\FoundOrganizationPage;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\SharedKernel\Application\FileResponse;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\SearchKey;
use Prospecter\Core\SharedKernel\Domain\SearchKeys;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Reads the organizations the in-memory repository saved, as the adapter reads the items of the table.
 */
final readonly class InMemoryFoundOrganizationRetriever implements FoundOrganizationRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function sliceProspectedBy(AccountId $accountId, ?SearchKey $search, ?string $after, int $max): FoundOrganizationPage
    {
        $prospected = array_values(array_filter($this->database->organizations(), static fn (Organization $organization): bool => $organization->isProspectedBy($accountId)));
        $slice = NewestFirst::slice($prospected, static fn (Organization $organization): string => (string) $organization->id(), static fn (Organization $organization): SearchKeys => $organization->searchKeys(), $search, $after, $max);

        return FoundOrganizationPage::of(new Slice(array_map(self::found(...), $slice->content), $slice->next));
    }

    private static function found(Organization $organization): FoundOrganization
    {
        $address = $organization->address();
        $geoPoint = $organization->geoPoint();

        return new FoundOrganization(
            id: $organization->id()->value,
            fullName: $organization->fullName(),
            postalCode: $address?->postalCode,
            city: $address?->city,
            country: $address?->country,
            logo: FileResponse::orNone($organization->logo()),
            longitude: $geoPoint?->longitude,
            latitude: $geoPoint?->latitude,
        );
    }
}
