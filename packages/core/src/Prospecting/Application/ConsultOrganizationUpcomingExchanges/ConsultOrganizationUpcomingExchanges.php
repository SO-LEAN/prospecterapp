<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganizationUpcomingExchanges;

/**
 * The exchanges with an organization and with its contacts after the moment, the soonest first, a slice after the
 * other.
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
