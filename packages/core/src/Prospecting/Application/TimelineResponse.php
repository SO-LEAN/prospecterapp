<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\Prospecting\Domain\TimelineIntent;
use Prospecter\Core\SharedKernel\Domain\Slice;

final readonly class TimelineResponse
{
    /**
     * @param list<ExchangeResponse> $exchanges
     * @param string|null            $next            the cursor of the next slice, null when this one is the last
     * @param list<string>           $possibleIntents names of what the timeline offers: ConsultTimeline, NoteExchange
     * @param int                    $toMakeConform   how many of its exchanges are to make conform, all of them, not only those of the slice
     */
    private function __construct(
        public array $exchanges,
        public ?string $next,
        public array $possibleIntents,
        public int $toMakeConform,
    ) {
    }

    /**
     * The timeline of a contact: none of its exchanges is to make conform while the contact is known.
     *
     * @param Slice<Exchange> $slice
     */
    public static function of(Slice $slice, Prospector $prospector): self
    {
        return new self(
            array_map(static fn (Exchange $exchange): ExchangeResponse => ExchangeResponse::of($exchange, $prospector), $slice->content),
            $slice->next,
            self::intentsOf(),
            0,
        );
    }

    /**
     * @param Slice<Exchange> $slice
     */
    public static function ofOrganization(Organization $organization, Slice $slice, int $toMakeConform, Prospector $prospector): self
    {
        return new self(
            array_map(static fn (Exchange $exchange): ExchangeResponse => ExchangeResponse::inTheTimelineOf($organization, $exchange, $prospector), $slice->content),
            $slice->next,
            self::intentsOf(),
            $toMakeConform,
        );
    }

    /**
     * @return list<string>
     */
    private static function intentsOf(): array
    {
        return array_map(static fn (TimelineIntent $intent): string => $intent->name, TimelineIntent::cases());
    }
}
