<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\SearchIndexes;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\Port\FoundOrganizationRetriever;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\FoundOrganization;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\FoundOrganizationPage;
use Prospecter\Core\SharedKernel\Application\FileResponse;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\SearchKey;

/**
 * Reads the items `ORGANIZATION#<id>` / `ORGANIZATION` that `DynamoOrganizationRepository` writes, as a list
 * shows them, without restoring the organizations (ADR 0088): listed by account in `GSI1`, found by the indexes
 * of the search (ADR 0071).
 */
final readonly class DynamoFoundOrganizationRetriever implements FoundOrganizationRetriever
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function sliceProspectedBy(AccountId $accountId, ?SearchKey $search, ?string $after, int $max): FoundOrganizationPage
    {
        $items = null === $search
            ? $this->prospectingTable->query('ACCOUNT#'.$accountId->value, Table::BY_GSI1, $after, $max + 1)
            : $this->inTheOrderOf(SearchIndexes::found($this->prospectingTable, $accountId->value, $search, null === $after ? null : OrganizationKeys::organization($after), $max + 1));
        $found = array_map(static fn (array $item): FoundOrganization => self::found($item['organization']), $items);

        return FoundOrganizationPage::of(Documents::slice($found, $max, static fn (FoundOrganization $organization): string => $organization->id));
    }

    /**
     * @param list<string> $keys partition keys of organizations
     *
     * @return list<array<string, mixed>>
     */
    private function inTheOrderOf(array $keys): array
    {
        $items = [];

        foreach ($this->prospectingTable->items(array_map(static fn (string $key): array => [$key, OrganizationKeys::ORGANIZATION], $keys)) as $item) {
            $items[$item['PK']] = $item;
        }

        return array_values(array_filter(array_map(static fn (string $key): ?array => $items[$key] ?? null, $keys)));
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function found(array $document): FoundOrganization
    {
        $address = Documents::addressOf($document['address']);
        $geoPoint = Documents::geoPointOf($document['geoPoint']);

        return new FoundOrganization(
            id: $document['id'],
            fullName: Documents::legalIdentityOf($document['legalIdentity'])->fullName(),
            postalCode: $address?->postalCode,
            city: $address?->city,
            country: $address?->country,
            logo: FileResponse::orNone(Documents::fileOf($document['logo'])),
            longitude: $geoPoint?->longitude,
            latitude: $geoPoint?->latitude,
        );
    }
}
