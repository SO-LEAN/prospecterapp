<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Timeline\ConsultTimelineIntents;

use Prospecter\Core\Prospecting\Domain\Model\TimelineIntent;

final readonly class TimelineIntentsResponse
{
    /**
     * @param list<string> $possibleIntents the names of what the timeline offers, none for a person who is not a contact: ConsultTimeline, NoteExchange, PlanExchange
     */
    private function __construct(public array $possibleIntents)
    {
    }

    /**
     * @param list<TimelineIntent> $intents
     */
    public static function of(array $intents): self
    {
        return new self(array_map(static fn (TimelineIntent $intent): string => $intent->name, $intents));
    }
}
