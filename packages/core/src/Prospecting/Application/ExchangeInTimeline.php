<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

/**
 * An exchange in a timeline. Its channel, direction and status take the values of `Channel`, `Direction` and
 * `ExchangeStatus`. A cancelled exchange names who called it off, when someone said so, by the values of `Canceller`.
 * A planned exchange has a duration. A meeting has its place, and a video call has its link.
 * The exchange names the contact it was with, or none when it was with the organization itself.
 * Its text is flagged when the prospector reads it without the right to manage personal data.
 * Once its contact is forgotten, it says what still needs redacting.
 * It says whether its contact waits to be attached. Until then, the exchange is kept for no organization (ADR 0146).
 * It names the campaign it was logged in, under the campaign's current name in Campaigns. It names none once the
 * campaign is erased (ADR 0145). It says whether its contact booked it through its holder's public link (ADR 0164).
 * It names the provider of the mailbox it was imported from, or none when it was not imported (ADR 0174).
 * It says whether an AI created it.
 */
final readonly class ExchangeInTimeline
{
    /**
     * @param string|null  $contactPresence a value of `ContactPresence`, or none for the organization itself
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
        public bool $withAContactToAttach,
        public ?string $campaignId = null,
        public ?string $campaignName = null,
        public bool $bookedByLink = false,
        public ?string $importedFrom = null,
        public bool $createdByAnAI = false,
    ) {
    }

    /**
     * @param array<string, string> $names the campaigns' names, by campaign id
     */
    #[\NoDiscard]
    public function withCampaignNamed(array $names): self
    {
        return null === $this->campaignId ? $this : clone ($this, ['campaignName' => $names[$this->campaignId] ?? null]);
    }
}
