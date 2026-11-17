<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

/**
 * An exchange in a timeline; its channel, its direction and its status by the values of `Channel`, `Direction` and
 * `ExchangeStatus`, who called it off once cancelled, when said, by the values of `Canceller`, how long it lasts when
 * it was planned, where a meeting is held, the link of a video call, the
 * contact it was with, none when it was with the organization itself, its text flagged when the prospector reads
 * it without the right to manage personal data, what is to make conform once its contact was forgotten, and the
 * campaign it was noted in, named as Campaigns names it now, none once erased (ADR 0145).
 */
final readonly class ExchangeInTimeline
{
    /**
     * @param string|null  $contactPresence by the values of `ContactPresence`, none for the organization itself
     * @param list<string> $organizationIds the organizations it is kept for
     */
    public function __construct(
        public string $id,
        public ?string $personId,
        public ?string $contactPresence,
        public string $channel,
        public string $direction,
        public \DateTimeImmutable $occurredAt,
        public string $status,
        public ?string $canceller,
        public ?int $durationInMinutes,
        public ?string $text,
        public ?string $place,
        public ?string $link,
        public bool $textFlagged,
        public bool $textToMakeConform,
        public bool $toMakeConform,
        public array $organizationIds,
        public int $attachmentCount,
        public ?string $campaignId = null,
        public ?string $campaignName = null,
    ) {
    }

    /**
     * @param array<string, string> $names of campaigns, by identity
     */
    #[\NoDiscard]
    public function withCampaignNamed(array $names): self
    {
        return null === $this->campaignId ? $this : clone ($this, ['campaignName' => $names[$this->campaignId] ?? null]);
    }
}
