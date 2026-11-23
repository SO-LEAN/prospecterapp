<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\Port\OrganizationsToLocateAgainRetriever;

/**
 * Reads the organizations located before Prospecter kept their municipality (ADR 0142). It reads them from the items
 * that `DynamoOrganizationRepository` writes, without restoring them (ADR 0088). It scans the table, so only a command
 * run once on an instance uses it, never a request.
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
