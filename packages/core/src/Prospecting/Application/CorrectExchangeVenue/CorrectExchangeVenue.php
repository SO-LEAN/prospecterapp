<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\CorrectExchangeVenue;

/**
 * Corrects where a meeting is held, or the link that joins a video call, planned or done. The exchange's other facts
 * stay. A place or a link left out is removed.
 */
final readonly class CorrectExchangeVenue
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public ?string $place,
        public ?string $link,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
