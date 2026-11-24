<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Timeline\ConsultOrganizationTimeline;

/**
 * Reads the exchanges with an organization and its contacts up to the given moment. The most recent come first, one
 * slice at a time.
 */
final readonly class ConsultOrganizationTimeline
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
