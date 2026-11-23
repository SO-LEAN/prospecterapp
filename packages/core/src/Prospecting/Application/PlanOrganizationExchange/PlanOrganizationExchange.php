<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\PlanOrganizationExchange;

/**
 * Plans a call, a meeting or a video call from an organization's card. The exchange is with the organization itself,
 * or with one of its contacts when `personId` names one. The prospector who plans it holds it. The channel takes the
 * values of `Channel`. The duration is in minutes, and is half an hour when left out. A meeting may have its place.
 * A video call may have its link, or request one from its holder's calendar (ADR 0137). `byAnAI` is set when an AI
 * plans it, acting as the prospector.
 */
final readonly class PlanOrganizationExchange
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public string $organizationId,
        public ?string $personId,
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
