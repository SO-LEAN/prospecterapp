<?php

namespace App\Prospecting\Presenter;

use App\Prospecting\Resource\ExchangeResource;
use App\Prospecting\Resource\TimelineLinksResource;
use App\Prospecting\Resource\TimelineResource;
use App\Shared\Links;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\ExchangeResponse;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\TimelineResponse;

final readonly class TimelinePresenter
{
    public function __construct(private Links $links)
    {
    }

    public function present(string $contactId, TimelineResponse $response): TimelineResource
    {
        $contact = ['id' => $contactId];

        return new TimelineResource(
            items: array_map($this->presentExchange(...), $response->exchanges),
            links: new TimelineLinksResource(
                self: $this->links->to('api_contacts_timeline', $contact),
                noteExchange: $this->links->to('api_contacts_note_exchange', $contact),
                next: null === $response->next ? null : $this->links->to('api_contacts_timeline', $contact + ['after' => $response->next]),
            ),
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
