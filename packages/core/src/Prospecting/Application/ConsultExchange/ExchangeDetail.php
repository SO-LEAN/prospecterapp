<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchange;

/**
 * An exchange as its detail shows it. The detail holds:
 * - its channel, direction and status, with the values of `Channel`, `Direction` and `ExchangeStatus`;
 * - for a cancelled exchange, who called it off when someone said so, with the values of `Canceller`;
 * - how long it lasts when it is planned;
 * - where a meeting is held, or the link that joins a video call, or whether the video call waits for a link from
 *   its holder's calendar (ADR 0137);
 * - the member ids of who logged it and who corrected it;
 * - the files attached to it. A reader without the right to manage personal data gets the text flagged and the
 *   files hidden, only counted;
 * - once its contact is forgotten, what still needs redacting, and who declared it redacted;
 * - the campaign it was logged in, with the name Campaigns gives it now, or none once the campaign is erased
 *   (ADR 0152);
 * - whether its contact booked it through its holder's public link (ADR 0164);
 * - for every reader, the provider of the mailbox it was imported from, or none when it was not imported;
 * - the id the provider gives its message in the importing mailbox, which opens the message there. An exchange
 *   imported before Prospecter kept that id has none (ADR 0174);
 * - whether an AI created it.
 */
final readonly class ExchangeDetail
{
    /**
     * @param string|null            $contactPresence by the values of `ContactPresence`, none for the organization itself
     * @param list<string>           $organizationIds the organizations it is kept for
     * @param list<AttachmentDetail> $attachments     the files attached to it, in the order they were attached
     * @param list<string>           $possibleIntents names of what its state offers its reader now: CorrectExchange, CorrectText, CorrectVenue, MakeConform, EraseExchange, AttachFile, RemoveAttachment, MarkExchangeDone, MarkExchangeMissed, MoveExchange, MoveExchangeBack, CancelExchange
     */
    public function __construct(
        public string $id,
        public string $accountId,
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
        public bool $linkAwaited,
        public bool $textFlagged,
        public bool $textToMakeConform,
        public bool $toMakeConform,
        public array $organizationIds,
        public string $createdBy,
        public \DateTimeImmutable $createdAt,
        public ?string $correctedBy,
        public ?\DateTimeImmutable $correctedAt,
        public ?string $madeConformBy,
        public ?\DateTimeImmutable $madeConformAt,
        public array $attachments,
        public int $hiddenAttachments,
        public array $possibleIntents,
        public ?string $campaignId = null,
        public ?string $campaignName = null,
        public bool $bookedByLink = false,
        public ?string $importedFrom = null,
        public ?string $providerMessageId = null,
        public bool $createdByAnAI = false,
    ) {
    }

    /**
     * @param array<string, string> $names the campaigns' names, by identity
     */
    #[\NoDiscard]
    public function withCampaignNamed(array $names): self
    {
        return clone ($this, ['campaignName' => $names[(string) $this->campaignId] ?? null]);
    }
}
