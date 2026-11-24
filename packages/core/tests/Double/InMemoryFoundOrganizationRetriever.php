<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\Organization\SearchProspectedOrganizations\FoundOrganization;
use Prospecter\Core\Prospecting\Application\Organization\SearchProspectedOrganizations\FoundOrganizationPage;
use Prospecter\Core\Prospecting\Application\Port\FoundOrganizationRetriever;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\SharedKernel\Application\Response\FileResponse;
use Prospecter\Core\SharedKernel\Domain\Company\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\Contact\Address;
use Prospecter\Core\SharedKernel\Domain\File\File;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Location\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\Search\SearchKey;
use Prospecter\Core\SharedKernel\Domain\Search\SearchKeys;
use Prospecter\Core\SharedKernel\Domain\Search\Slice;

/**
 * Reads the organizations that the in-memory repository saved, as the adapter reads the table's items.
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

        return FoundOrganizationPage::of(new Slice(array_map($this->found(...), $slice->content), $slice->next));
    }

    private function found(Organization $organization): FoundOrganization
    {
        $state = StateOf::the($organization);
        $address = $state->objectOrNull('address', Address::class);
        $geoPoint = $state->objectOrNull('geoPoint', GeoPoint::class);

        return new FoundOrganization(
            id: $organization->id()->value,
            fullName: $state->object('legalIdentity', LegalIdentity::class)->fullName(),
            postalCode: $address?->postalCode,
            city: $address?->city,
            country: $address?->country,
            logo: FileResponse::orNone($state->objectOrNull('logo', File::class)),
            longitude: $geoPoint?->longitude,
            latitude: $geoPoint?->latitude,
            possibleDuplicates: PossibleDuplicates::of($organization, $this->database),
        );
    }
}
