<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultTimeline;

/**
 * Reads the exchanges with a contact up to the given moment. The most recent come first, one slice at a time.
 */
final readonly class ConsultTimeline
{
    public function __construct(
        public string $prospectorId,
        public string $personId,
        public \DateTimeImmutable $asOf,
        public ?string $after = null,
        public int $max = 20,
    ) {
    }
}
