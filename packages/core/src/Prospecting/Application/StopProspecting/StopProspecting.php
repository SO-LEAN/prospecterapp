<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\StopProspecting;

/**
 * Removes the organization from the prospecting base. Its subsidiaries lose their holding. Its contacts that no other
 * organization names are erased (ADR 0042).
 */
final readonly class StopProspecting
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
