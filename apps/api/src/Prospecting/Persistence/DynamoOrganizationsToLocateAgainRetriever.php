<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\Port\OrganizationsToLocateAgainRetriever;

/**
 * The organizations located before their municipality was kept (ADR 0142), read from the items of
 * `DynamoOrganizationRepository` without restoring them (ADR 0088): a scan of the table, for a command run once on
 * an instance, never by a request.
 */
final readonly class DynamoOrganizationsToLocateAgainRetriever implements OrganizationsToLocateAgainRetriever
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function all(): iterable
    {
        foreach ($this->prospectingTable->scan(OrganizationKeys::organization('')) as $item) {
            if (OrganizationKeys::ORGANIZATION !== $item['SK']) {
                continue;
            }
            $organization = Typed::document($item['organization']);

            if (self::locatedWithoutItsMunicipality($organization)) {
                yield Typed::string($organization['id']);
            }
        }
    }

    /**
     * @param array<string, mixed> $organization
     */
    private static function locatedWithoutItsMunicipality(array $organization): bool
    {
        return null !== $organization['geoPoint'] && null === ($organization['municipalityCode'] ?? null);
    }
}
