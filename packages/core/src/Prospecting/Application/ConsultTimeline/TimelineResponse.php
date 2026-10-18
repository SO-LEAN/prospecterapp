<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultTimeline;

use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\Prospecting\Domain\TimelineIntent;
use Prospecter\Core\SharedKernel\Domain\Slice;

final readonly class TimelineResponse
{
    /**
     * @param list<ExchangeResponse> $exchanges
     * @param string|null            $next            the cursor of the next slice, null when this one is the last
     * @param list<string>           $possibleIntents names of what the prospector may do with the timeline: NoteExchange
     */
    private function __construct(
        public array $exchanges,
        public ?string $next,
        public array $possibleIntents,
    ) {
    }

    /**
     * @param Slice<Exchange> $slice
     */
    public static function of(Slice $slice, Prospector $prospector): self
    {
        return new self(
            array_map(ExchangeResponse::of(...), $slice->content),
            $slice->next,
            array_map(static fn (TimelineIntent $intent): string => $intent->name, $prospector->timelineIntents()),
        );
    }
}
