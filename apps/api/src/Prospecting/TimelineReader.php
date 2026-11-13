<?php

namespace App\Prospecting;

use App\Prospecting\Presenter\TimelinePresenter;
use App\Prospecting\Resource\TimelineResource;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\ConsultTimeline;
use Prospecter\Core\Prospecting\Application\ConsultUpcomingExchanges\ConsultUpcomingExchanges;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * The timeline of a contact as the API shows it, what happened until now or what is to come: read by the query of
 * the core, presented to the presenter it builds for the call (ADR 0085). A controller calls it after a command, which returns nothing, to answer with the
 * fresh resource.
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
        ($this->consultTimeline)(new ConsultTimeline($prospector, $contactId, $this->clock->now(), $after), $presenter);

        return $presenter->resource;
    }

    public function readUpcoming(string $prospector, string $contactId, ?string $after = null): TimelineResource
    {
        $presenter = TimelinePresenter::ofWhatIsToCome($contactId, $this->timelines);
        ($this->consultUpcomingExchanges)(new ConsultUpcomingExchanges($prospector, $contactId, $this->clock->now(), $after), $presenter);

        return $presenter->resource;
    }
}
