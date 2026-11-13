<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\PlanOrganizationExchange;

/**
 * Plans, from the card of an organization, a call, a meeting or a video call with the organization itself, or
 * with one of its contacts when `personId` names one, held by the prospector who plans it. The channel is named
 * by the values of `Channel`; the duration, in minutes, is half an hour when left out.
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
    ) {
    }
}
