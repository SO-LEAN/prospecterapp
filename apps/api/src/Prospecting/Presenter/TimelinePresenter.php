<?php

namespace App\Prospecting\Presenter;

use App\Prospecting\Resource\TimelineResource;
use App\Prospecting\TimelineResourceFactory;
use Prospecter\Core\Prospecting\Application\Timeline;
use Prospecter\Core\Prospecting\Application\TimelineOutput;

/**
 * A slice of the timeline of a contact, with a link per intention the prospector may carry out on it. Built for one
 * call, it keeps the resource (ADR 0085).
 */
final class TimelinePresenter implements TimelineOutput
{
    private const array CONTACT_ROUTES = ['api_contacts_timeline', 'api_contacts_note_exchange'];

    public private(set) TimelineResource $resource;

    public function __construct(private readonly string $contactId, private readonly TimelineResourceFactory $timelineResourceFactory)
    {
    }

    public function present(Timeline $timeline): void
    {
        $this->resource = new TimelineResource(
            items: array_map($this->timelineResourceFactory->createExchange(...), $timeline->exchanges),
            links: $this->timelineResourceFactory->createLinks(self::CONTACT_ROUTES, ['id' => $this->contactId], $timeline),
        );
    }
}
