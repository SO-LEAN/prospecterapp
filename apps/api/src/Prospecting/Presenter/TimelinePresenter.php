<?php

namespace App\Prospecting\Presenter;

use App\Prospecting\Resource\ExchangeLinksResource;
use App\Prospecting\Resource\ExchangeResource;
use App\Prospecting\Resource\TimelineLinksResource;
use App\Prospecting\Resource\TimelineResource;
use App\Shared\Links;
use Prospecter\Core\Prospecting\Application\ExchangeResponse;
use Prospecter\Core\Prospecting\Application\TimelineResponse;

/**
 * A slice of the timeline of a contact, with a link per intention the prospector may carry out on it; its
 * exchanges and its links serve the timeline of an organization too.
 */
final readonly class TimelinePresenter
{
    private const array CONTACT_ROUTES = ['api_contacts_timeline', 'api_contacts_note_exchange'];

    public function __construct(private Links $links)
    {
    }

    public function present(string $contactId, TimelineResponse $response): TimelineResource
    {
        return new TimelineResource(
            items: array_map($this->presentExchange(...), $response->exchanges),
            links: $this->linksOf(self::CONTACT_ROUTES, ['id' => $contactId], $response),
        );
    }

    /**
     * Itself, the next slice, and noting an exchange when the prospector may.
     *
     * @param array{string, string} $routes  the route of the timeline and the route noting an exchange in it
     * @param array<string, string> $subject the parameters naming whose timeline it is
     */
    public function linksOf(array $routes, array $subject, TimelineResponse $response): TimelineLinksResource
    {
        [$timeline, $noteExchange] = $routes;

        return new TimelineLinksResource(
            $this->links->to($timeline, $subject),
            null === $response->next ? null : $this->links->to($timeline, $subject + ['after' => $response->next]),
            in_array('NoteExchange', $response->possibleIntents, true) ? $this->links->to($noteExchange, $subject) : null,
        );
    }

    public function presentExchange(ExchangeResponse $exchange): ExchangeResource
    {
        return new ExchangeResource(
            id: $exchange->id,
            channel: $exchange->channel,
            direction: $exchange->direction,
            missed: $exchange->missed,
            occurredAt: $exchange->occurredAt->format(\DateTimeInterface::ATOM),
            text: $exchange->text,
            organizationIds: $exchange->organizationIds,
            attachmentCount: $exchange->attachmentCount,
            links: new ExchangeLinksResource($this->links->to('api_exchanges_show', ['id' => $exchange->id])),
        );
    }
}
