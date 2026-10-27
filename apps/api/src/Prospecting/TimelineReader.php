<?php

namespace App\Prospecting;

use App\Prospecting\Presenter\TimelinePresenter;
use App\Prospecting\Resource\TimelineResource;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\ConsultTimeline;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;

/**
 * The timeline of a contact as the API shows it: read by the query of the core, presented to the presenter it
 * builds for the call (ADR 0085). A controller calls it after a command, which returns nothing, to answer with the
 * fresh resource.
 */
final readonly class TimelineReader
{
    public function __construct(private RefusalTranslatingHandler $consultTimeline, private TimelineResourceFactory $timelines)
    {
    }

    public function read(string $prospector, string $contactId, ?string $after = null): TimelineResource
    {
        $presenter = new TimelinePresenter($contactId, $this->timelines);
        ($this->consultTimeline)(new ConsultTimeline($prospector, $contactId, $after), $presenter);

        return $presenter->resource;
    }
}
