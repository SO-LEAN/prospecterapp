<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchange;

use Prospecter\Core\Prospecting\Domain\Model\Attachment;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * An exchange as its detail shows it; its channel and its direction by the values of `Channel` and
 * `Direction`, who noted and corrected it by their member id, the files joined to it; read without the right to
 * manage personal data, its text flagged and its files hidden, only counted; its contact forgotten, what is to make
 * conform, and who declared it made conform.
 */
final readonly class ExchangeDetailResponse
{
    /**
     * @param string|null              $contactPresence by the values of `ContactPresence`, none for the organization itself
     * @param list<string>             $organizationIds the organizations it is kept for
     * @param list<AttachmentResponse> $attachments     the files joined to it, in the order they were
     * @param list<string>             $possibleIntents names of what its state offers now: CorrectExchange, CorrectText, MakeConform, EraseExchange, AttachFile, RemoveAttachment
     */
    private function __construct(
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

    public static function of(Exchange $exchange, Prospector $prospector): self
    {
        $facts = $exchange->facts();
        $correction = $exchange->lastCorrection();
        $madeConform = $exchange->madeConform();
        $attachments = $exchange->attachmentsReadBy($prospector);

        return new self(
            id: $exchange->id()->value,
            accountId: $exchange->accountId()->value,
            personId: $exchange->contact()?->value,
            contactPresence: $exchange->contactPresence()?->value,
            channel: $facts->channel->value,
            direction: $facts->direction->value,
            missed: $facts->missed,
            occurredAt: $facts->occurredAt,
            text: $facts->text,
            textFlagged: $exchange->isTextFlaggedFor($prospector),
            textToMakeConform: $exchange->isTextToMakeConform(),
            toMakeConform: $exchange->isToMakeConform(),
            organizationIds: array_map(static fn (OrganizationId $organization): string => $organization->value, $exchange->keptFor()),
            createdBy: $exchange->createdBy()->value,
            createdAt: $exchange->createdAt(),
            correctedBy: $correction?->by->value,
            correctedAt: $correction?->at,
            madeConformBy: $madeConform?->by->value,
            madeConformAt: $madeConform?->at,
            attachments: array_map(static fn (Attachment $attachment): AttachmentResponse => AttachmentResponse::of($attachment, $exchange), $attachments),
            hiddenAttachments: count($exchange->attachments()) - count($attachments),
            possibleIntents: array_map(static fn (ExchangeIntent $intent): string => $intent->name, $exchange->possibleIntents()),
        );
    }
}
