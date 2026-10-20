<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganizationTimeline;

/**
 * The exchanges with an organization and with its contacts, the most recent first, a slice after the other.
 */
final readonly class ConsultOrganizationTimeline
{
    public function __construct(
        public string $prospectorId,
        public string $organizationId,
        public ?string $after = null,
        public int $max = 20,
    ) {
    }
}
