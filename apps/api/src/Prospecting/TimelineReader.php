<?php

declare(strict_types=1);

namespace App\Prospecting;

use App\Prospecting\Presenter\TimelinePresenter;
use App\Prospecting\Resource\TimelineResource;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\ConsultTimeline;
use Prospecter\Core\Prospecting\Application\ConsultUpcomingExchanges\ConsultUpcomingExchanges;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * Reads a contact's timeline as the API shows it: what has happened so far, or what is to come. The core's query reads
 * it, and a presenter built for the call presents it (ADR 0085). A command returns nothing, so a controller calls this
 * reader after the command to answer with the fresh resource.
 */
final readonly class TimelineReader
{
    public function __construct(
        private QueryHandler $consultTimeline,
        private QueryHandler $consultUpcomingExchanges,
        private TimelineResourceFactory $timelines,
        private ClockInterface $clock,
    ) {
    }

    public function read(string $prospector, string $contactId, ?string $after = null): TimelineResource
    {
        $presenter = TimelinePresenter::ofThePast($contactId, $this->timelines);
        $this->consultTimeline->__invoke(new ConsultTimeline(prospectorId: $prospector, personId: $contactId, asOf: $this->clock->now(), after: $after), $presenter);

        return $presenter->resource;
    }

    public function readUpcoming(string $prospector, string $contactId, ?string $after = null): TimelineResource
    {
        $presenter = TimelinePresenter::ofWhatIsToCome($contactId, $this->timelines);
        $this->consultUpcomingExchanges->__invoke(new ConsultUpcomingExchanges(prospectorId: $prospector, personId: $contactId, asOf: $this->clock->now(), after: $after), $presenter);

        return $presenter->resource;
    }
}
