<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultTimelineIntents;

use Prospecter\Core\Prospecting\Domain\TimelineIntent;

final readonly class TimelineIntentsResponse
{
    /**
     * @param list<string> $possibleIntents names of what the prospector may do with the timeline: ConsultTimeline, NoteExchange
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
