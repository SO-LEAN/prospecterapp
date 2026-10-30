<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchange;

/**
 * An exchange as its detail shows it; its channel and its direction by the values of `Channel` and
 * `Direction`, who noted and corrected it by their member id, the files joined to it; read without the right to
 * manage personal data, its text flagged and its files hidden, only counted; its contact forgotten, what is to make
 * conform, and who declared it made conform.
 */
final readonly class ExchangeDetail
{
    /**
     * @param string|null            $contactPresence by the values of `ContactPresence`, none for the organization itself
     * @param list<string>           $organizationIds the organizations it is kept for
     * @param list<AttachmentDetail> $attachments     the files joined to it, in the order they were
     * @param list<string>           $possibleIntents names of what its state offers now: CorrectExchange, CorrectText, MakeConform, EraseExchange, AttachFile, RemoveAttachment
     */
    public function __construct(
        public string $id,
        public string $accountId,
        public ?string $personId,
        public ?string $contactPresence,
        public string $channel,
        public string $direction,
        public bool $missed,
        public \DateTimeImmutable $occurredAt,
        public ?string $text,
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
    ) {
    }
}
