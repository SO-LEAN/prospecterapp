<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Timeline;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;

/**
 * A slice of the exchanges with a contact or with an organization. The past ones come most recent first, and those to
 * come soonest first.
 */
final readonly class Timeline
{
    /**
     * @param list<ExchangeInTimeline> $exchanges
     * @param string|null              $next            the next slice's cursor, null when this one is the last
     * @param list<string>             $possibleIntents the names of what the timeline offers: ConsultTimeline, NoteExchange, PlanExchange
     * @param int                      $toMakeConform   how many of its exchanges still need redacting, counting all of them and not only the slice's. It is known while the timeline is read, and is zero in a contact's timeline
     */
    public function __construct(
        public array $exchanges,
        public ?string $next,
        public array $possibleIntents,
        public int $toMakeConform,
    ) {
    }

    /**
     * @return list<ContactId> the contacts its exchanges were with
     */
    #[\NoDiscard]
    public function contacts(): array
    {
        $personIds = array_values(array_filter(array_map(static fn (ExchangeInTimeline $exchange): ?string => $exchange->personId, $this->exchanges)));

        return array_map(static fn (string $personId): ContactId => new ContactId($personId), $personIds);
    }

    /**
     * @return list<string> the ids of the campaigns its exchanges were logged in, each once
     */
    #[\NoDiscard]
    public function campaignIds(): array
    {
        return array_values(array_unique(array_filter(array_map(static fn (ExchangeInTimeline $exchange): ?string => $exchange->campaignId, $this->exchanges))));
    }

    /**
     * @param array<string, string> $names the campaigns' names, by id
     */
    #[\NoDiscard]
    public function withCampaignsNamed(array $names): self
    {
        return clone ($this, ['exchanges' => array_map(static fn (ExchangeInTimeline $exchange): ExchangeInTimeline => $exchange->withCampaignNamed($names), $this->exchanges)]);
    }

    /**
     * @param list<string> $possibleIntents what the timeline offers its reader, by name
     */
    #[\NoDiscard]
    public function offering(array $possibleIntents): self
    {
        return clone ($this, ['possibleIntents' => $possibleIntents]);
    }
}
