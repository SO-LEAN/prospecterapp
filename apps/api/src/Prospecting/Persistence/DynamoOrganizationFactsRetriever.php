<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Revision;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\Port\OrganizationFactsRetriever;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\OrganizationFactsResponse;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Reads the organizations' facts, with their revision, from the items `ORGANIZATION#<id>` / `ORGANIZATION` that
 * `DynamoOrganizationRepository` writes. It does not restore the organizations (ADR 0088). A read by key sees what the
 * open unit of work is about to write. A list reads an account's organizations in `GSI1`.
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
        $items = $this->prospectingTable->query(OrganizationKeys::ofAccount($accountId->value), Table::BY_GSI1, $after, $max + 1);

        return Documents::slice(array_map(self::factsOf(...), $items), $max, static fn (OrganizationFactsResponse $facts): string => $facts->organizationId);
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function factsOf(array $item): OrganizationFactsResponse
    {
        $organization = Typed::document($item['organization']);

        return new OrganizationFactsResponse(Typed::string($organization['id']), Revision::of($item), true, null !== $organization['geoPoint'], Typed::strings($organization['contacts']), Typed::stringOrNull($organization['municipalityCode'] ?? null), Documents::addressOf($organization['address'])?->country);
    }
}
