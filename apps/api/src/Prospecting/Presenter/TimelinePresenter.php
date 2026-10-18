<?php

namespace App\Prospecting\Presenter;

use App\Prospecting\Resource\ExchangeResource;
use App\Prospecting\Resource\TimelineLinksResource;
use App\Prospecting\Resource\TimelineResource;
use App\Shared\Links;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\ExchangeResponse;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\TimelineResponse;

/**
 * A slice of the timeline, with a link per intention the prospector may carry out on it.
 */
final readonly class TimelinePresenter
{
    private const array ROUTES = [
        'NoteExchange' => ['noteExchange', 'api_contacts_note_exchange'],
    ];

    public function __construct(private Links $links)
    {
    }

    public function present(string $contactId, TimelineResponse $response): TimelineResource
    {
        return new TimelineResource(
            items: array_map($this->presentExchange(...), $response->exchanges),
            links: $this->linksOf($contactId, $response),
        );
    }

    private function linksOf(string $contactId, TimelineResponse $response): TimelineLinksResource
    {
        $contact = ['id' => $contactId];
        $possible = [];

        foreach ($response->possibleIntents as $intent) {
            if (isset(self::ROUTES[$intent])) {
                [$name, $route] = self::ROUTES[$intent];
                $possible[$name] = $this->links->to($route, $contact);
            }
        }

        return new TimelineLinksResource(
            $this->links->to('api_contacts_timeline', $contact),
            null === $response->next ? null : $this->links->to('api_contacts_timeline', $contact + ['after' => $response->next]),
            ...$possible,
        );
    }

    private function presentExchange(ExchangeResponse $exchange): ExchangeResource
    {
        return new ExchangeResource(
            id: $exchange->id,
            channel: $exchange->channel,
            direction: $exchange->direction,
            missed: $exchange->missed,
            occurredAt: $exchange->occurredAt->format(\DateTimeInterface::ATOM),
            text: $exchange->text,
            organizationIds: $exchange->organizationIds,
        );
    }
}
