<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\PlanExchange;

/**
 * Plans a call, a meeting or a video call with a contact, held by the prospector who plans it, kept for the
 * organizations the contact is part of now. The channel is named by the values of `Channel`; the duration, in
 * minutes, is half an hour when left out.
 */
final readonly class PlanExchange
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public string $personId,
        public string $channel,
        public \DateTimeImmutable $plannedAt,
        public ?int $durationInMinutes,
        public ?string $agenda,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
