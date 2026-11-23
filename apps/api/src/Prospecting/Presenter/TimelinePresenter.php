<?php

declare(strict_types=1);

namespace App\Prospecting\Presenter;

use App\Prospecting\Resource\TimelineResource;
use App\Prospecting\TimelineResourceFactory;
use Prospecter\Core\Prospecting\Application\Timeline;
use Prospecter\Core\Prospecting\Application\TimelineOutput;

/**
 * Presents a slice of a contact's timeline, with a link for each intention the prospector may carry out on it. It is
 * built for one call and keeps the resource (ADR 0085).
 */
final class TimelinePresenter implements TimelineOutput
{
    private const array ROUTES = ['api_contacts_timeline', 'api_contacts_note_exchange', 'api_contacts_plan_exchange'];
    private const array UPCOMING_ROUTES = ['api_contacts_upcoming_exchanges', 'api_contacts_note_exchange', 'api_contacts_plan_exchange'];

    public private(set) TimelineResource $resource;

    /**
     * @param array{string, string, string} $routes the list's route, the route that logs an exchange and the route that plans one
     */
    private function __construct(private readonly array $routes, private readonly string $contactId, private readonly TimelineResourceFactory $timelineResourceFactory)
    {
    }

    public static function ofThePast(string $contactId, TimelineResourceFactory $timelines): self
    {
        return new self(self::ROUTES, $contactId, $timelines);
    }

    public static function ofWhatIsToCome(string $contactId, TimelineResourceFactory $timelines): self
    {
        return new self(self::UPCOMING_ROUTES, $contactId, $timelines);
    }

    public function present(Timeline $timeline): void
    {
        $this->resource = new TimelineResource(
            items: array_map($this->timelineResourceFactory->createExchange(...), $timeline->exchanges),
            links: $this->timelineResourceFactory->createLinks($this->routes, ['id' => $this->contactId], $timeline),
        );
    }
}
