<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchange;

use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\Prospecting\Domain\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * An exchange as its detail shows it; its channel and its direction by the values of `Channel` and
 * `Direction`, who noted and corrected it by their member id, the files joined to it.
 */
final readonly class ExchangeDetailResponse
{
    /**
     * @param list<string>             $organizationIds the organizations it is kept for
     * @param list<AttachmentResponse> $attachments     the files joined to it, in the order they were
     * @param list<string>             $possibleIntents names of what the prospector may do with it: CorrectExchange, EraseExchange, AttachFile, RemoveAttachment
     */
    private function __construct(
        public string $id,
        public ?string $personId,
        public string $channel,
        public string $direction,
        public bool $missed,
        public \DateTimeImmutable $occurredAt,
        public ?string $text,
        public array $organizationIds,
        public string $createdBy,
        public \DateTimeImmutable $createdAt,
        public ?string $correctedBy,
        public ?\DateTimeImmutable $correctedAt,
        public array $attachments,
        public array $possibleIntents,
    ) {
    }

    public static function of(Exchange $exchange, Prospector $prospector): self
    {
        $facts = $exchange->facts();
        $correction = $exchange->lastCorrection();

        return new self(
            id: $exchange->id()->value,
            personId: $exchange->contact()?->value,
            channel: $facts->channel->value,
            direction: $facts->direction->value,
            missed: $facts->missed,
            occurredAt: $facts->occurredAt,
            text: $facts->text,
            organizationIds: array_map(static fn (OrganizationId $organization): string => $organization->value, $exchange->keptFor()),
            createdBy: $exchange->createdBy()->value,
            createdAt: $exchange->createdAt(),
            correctedBy: $correction?->by->value,
            correctedAt: $correction?->at,
            attachments: array_map(AttachmentResponse::of(...), $exchange->attachments()),
            possibleIntents: array_map(static fn (ExchangeIntent $intent): string => $intent->name, $exchange->possibleIntentsOf($prospector)),
        );
    }
}
