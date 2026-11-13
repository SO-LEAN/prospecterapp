<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultUpcomingExchanges;

/**
 * The exchanges with a contact after the moment, the soonest first, a slice after the other: the first is the next
 * exchange planned with them.
 */
final readonly class ConsultUpcomingExchanges
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
