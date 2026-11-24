<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Timeline\ConsultUpcomingExchanges;

/**
 * Reads the exchanges with a contact after the given moment. The soonest come first, one slice at a time. The first
 * one is the next exchange planned with the contact.
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
