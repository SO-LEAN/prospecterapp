<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\PlanExchange;

/**
 * Plans a call, a meeting or a video call with a contact. The prospector who plans it holds it. The exchange is kept
 * for the organizations the contact is part of now. The channel takes the values of `Channel`. The duration is in
 * minutes, and is half an hour when left out. A meeting may have its place. A video call may have its link, or request
 * one from its holder's calendar (ADR 0137). `byAnAI` is set when an AI plans it, acting as the prospector.
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
        public ?string $place = null,
        public ?string $link = null,
        public bool $linkRequested = false,
        public bool $byAnAI = false,
    ) {
    }
}
