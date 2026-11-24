<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Timeline\ConsultOrganizationUpcomingExchanges;

/**
 * Reads the exchanges with an organization and its contacts after the given moment. The soonest come first, one
 * slice at a time.
 */
final readonly class ConsultOrganizationUpcomingExchanges
{
    public function __construct(
        public string $prospectorId,
        public string $organizationId,
        public \DateTimeImmutable $asOf,
        public ?string $after = null,
        public int $max = 20,
    ) {
    }
}
