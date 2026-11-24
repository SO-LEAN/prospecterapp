<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\SearchIndexes;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\Organization\SearchProspectedOrganizations\FoundOrganization;
use Prospecter\Core\Prospecting\Application\Organization\SearchProspectedOrganizations\FoundOrganizationPage;
use Prospecter\Core\Prospecting\Application\Port\FoundOrganizationRetriever;
use Prospecter\Core\Prospecting\Application\PossibleDuplicate;
use Prospecter\Core\SharedKernel\Application\Response\FileResponse;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Search\SearchKey;

/**
 * Reads the items `ORGANIZATION#<id>` / `ORGANIZATION` that `DynamoOrganizationRepository` writes, as a list shows
 * them. It does not restore the organizations (ADR 0088). It lists an account's organizations in `GSI1`, and finds
 * them through the search indexes (ADR 0071).
 */
final readonly class DynamoFoundOrganizationRetriever implements FoundOrganizationRetriever
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function sliceProspectedBy(AccountId $accountId, ?SearchKey $search, ?string $after, int $max): FoundOrganizationPage
    {
        $items = null === $search
            ? $this->prospectingTable->query(OrganizationKeys::ofAccount($accountId->value), Table::BY_GSI1, $after, $max + 1)
            : $this->prospectingTable->itemsInTheOrderOf(SearchIndexes::found($this->prospectingTable, $accountId->value, $search, null === $after ? null : OrganizationKeys::organization($after), $max + 1), OrganizationKeys::ORGANIZATION);
        $documents = Typed::documents(array_column($items, 'organization'));
        $possibleDuplicates = PossibleDuplicates::of($this->prospectingTable, $documents);
        $found = array_map(static fn (array $document): FoundOrganization => self::found($document, $possibleDuplicates[Typed::string($document['id'])] ?? []), $documents);

        return FoundOrganizationPage::of(Documents::slice($found, $max, static fn (FoundOrganization $organization): string => $organization->id));
    }

    /**
     * @param array<string, mixed>    $document
     * @param list<PossibleDuplicate> $possibleDuplicates
     */
    private static function found(array $document, array $possibleDuplicates): FoundOrganization
    {
        $address = Documents::addressOf($document['address']);
        $geoPoint = Documents::geoPointOf($document['geoPoint']);

        return new FoundOrganization(
            id: Typed::string($document['id']),
            fullName: Documents::legalIdentityOf($document['legalIdentity'])->fullName(),
            postalCode: $address?->postalCode,
            city: $address?->city,
            country: $address?->country,
            logo: FileResponse::orNone(Documents::fileOf($document['logo'])),
            longitude: $geoPoint?->longitude,
            latitude: $geoPoint?->latitude,
            possibleDuplicates: $possibleDuplicates,
        );
    }
}
