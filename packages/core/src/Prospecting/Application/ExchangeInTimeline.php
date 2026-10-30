<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

/**
 * An exchange in a timeline; its channel and its direction by the values of `Channel` and `Direction`, the
 * contact it was with, none when it was with the organization itself, its text flagged when the prospector reads
 * it without the right to manage personal data, and what is to make conform once its contact was forgotten.
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
}
