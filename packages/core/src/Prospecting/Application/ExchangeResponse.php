<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

use Prospecter\Core\Prospecting\Domain\ContactPresence;
use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * An exchange in a timeline; its channel and its direction by the values of `Channel` and `Direction`, the
 * contact it was with, none when it was with the organization itself, its text flagged when the prospector reads
 * it without the right to manage personal data, and what is to make conform once its contact was forgotten.
 */
final readonly class ExchangeResponse
{
    /**
     * @param string|null  $contactPresence by the values of `ContactPresence`, none for the organization itself
     * @param list<string> $organizationIds the organizations it is kept for
     */
    private function __construct(
        public string $id,
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
        public int $attachmentCount,
    ) {
    }

    /**
     * In the timeline of its contact.
     */
    public static function of(Exchange $exchange, Prospector $prospector): self
    {
        return self::withPresence($exchange, $exchange->contactPresence(), $prospector);
    }

    /**
     * In the timeline of an organization: its contact may have left it since.
     */
    public static function inTheTimelineOf(Organization $organization, Exchange $exchange, Prospector $prospector): self
    {
        return self::withPresence($exchange, $exchange->contactPresenceIn($organization), $prospector);
    }

    private static function withPresence(Exchange $exchange, ?ContactPresence $presence, Prospector $prospector): self
    {
        $facts = $exchange->facts();

        return new self(
            id: $exchange->id()->value,
            personId: $exchange->contact()?->value,
            contactPresence: $presence?->value,
            channel: $facts->channel->value,
            direction: $facts->direction->value,
            missed: $facts->missed,
            occurredAt: $facts->occurredAt,
            text: $facts->text,
            textFlagged: $exchange->isTextFlaggedFor($prospector),
            textToMakeConform: $exchange->isTextToMakeConform(),
            toMakeConform: $exchange->isToMakeConform(),
            organizationIds: array_map(static fn (OrganizationId $organization): string => $organization->value, $exchange->keptFor()),
            attachmentCount: count($exchange->attachments()),
        );
    }
}
