<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\Port\OrganizationsToLocateAgainRetriever;
use Prospecter\Core\Prospecting\Domain\Model\Organization;

/**
 * The organizations the in-memory repository saved with a position and without their municipality, as the adapter
 * scans the items of the table.
 */
final readonly class InMemoryOrganizationsToLocateAgainRetriever implements OrganizationsToLocateAgainRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function all(): iterable
    {
        foreach ($this->database->organizations() as $organization) {
            if (self::locatedWithoutItsMunicipality($organization)) {
                yield (string) $organization->id();
            }
        }
    }

    private static function locatedWithoutItsMunicipality(Organization $organization): bool
    {
        $state = StateOf::the($organization);

        return null !== $state->value('geoPoint') && null === $state->value('municipalityCode');
    }
}
