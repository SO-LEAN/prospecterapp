<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

/**
 * A slice of the exchanges with a contact or with an organization: the past ones the most recent moment first, those
 * to come the soonest first.
 */
final readonly class Timeline
{
    /**
     * @param list<ExchangeInTimeline> $exchanges
     * @param string|null              $next            the cursor of the next slice, null when this one is the last
     * @param list<string>             $possibleIntents names of what the timeline offers: ConsultTimeline, NoteExchange, PlanExchange
     * @param int                      $toMakeConform   how many of its exchanges are to make conform, all of them, not only those of the slice; none in the timeline of a contact, known while it is read
     */
    public function __construct(
        public array $exchanges,
        public ?string $next,
        public array $possibleIntents,
        public int $toMakeConform,
    ) {
    }
}
