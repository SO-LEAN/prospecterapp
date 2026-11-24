<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Organization\LocateAgain;

/**
 * Geocodes again an organization's address when the organization was located before Prospecter kept municipalities
 * (ADR 0142). Nothing happens for an organization that already has its municipality.
 */
final readonly class LocateAgain
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
