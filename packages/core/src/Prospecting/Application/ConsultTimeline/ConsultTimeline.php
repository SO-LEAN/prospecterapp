<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultTimeline;

/**
 * The exchanges with a contact, the most recent first, a slice after the other.
 */
final readonly class ConsultTimeline
{
    public function __construct(
        public string $prospectorId,
        public string $personId,
        public ?string $after = null,
        public int $max = 20,
    ) {
    }
}
