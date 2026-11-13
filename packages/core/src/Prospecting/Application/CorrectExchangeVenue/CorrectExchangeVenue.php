<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\CorrectExchangeVenue;

/**
 * Corrects where a meeting is held, or the link a video call is joined by, planned or done; its other facts stay.
 * Left out, they are removed.
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
