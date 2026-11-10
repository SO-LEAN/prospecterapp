<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Revision;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\Port\OrganizationFactsRetriever;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\OrganizationFactsResponse;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Reads the facts of the organizations in the items `ORGANIZATION#<id>` / `ORGANIZATION` that
 * `DynamoOrganizationRepository` writes, their revision with them, without restoring them (ADR 0088): by key, as
 * the open unit of work is about to write them, and listed by account in `GSI1`.
 */
final readonly class DynamoOrganizationFactsRetriever implements OrganizationFactsRetriever
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function of(OrganizationId $id): OrganizationFactsResponse
    {
        $item = $this->prospectingTable->item(OrganizationKeys::organization($id->value), OrganizationKeys::ORGANIZATION);

        return null === $item ? OrganizationFactsResponse::absent($id->value) : self::factsOf($item);
    }

    public function sliceOf(AccountId $accountId, ?string $after, int $max): Slice
    {
        $items = $this->prospectingTable->query('ACCOUNT#'.$accountId->value, Table::BY_GSI1, $after, $max + 1);

        return Documents::slice(array_map(self::factsOf(...), $items), $max, static fn (OrganizationFactsResponse $facts): string => $facts->organizationId);
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function factsOf(array $item): OrganizationFactsResponse
    {
        return new OrganizationFactsResponse($item['organization']['id'], Revision::of($item), true, null !== $item['organization']['geoPoint']);
    }
}
