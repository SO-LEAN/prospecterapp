<?php

namespace App\Prospecting;

use App\Prospecting\Resource\ExchangeLinksResource;
use App\Prospecting\Resource\ExchangeResource;
use App\Prospecting\Resource\TimelineLinksResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\Timeline;

/**
 * What the timelines of a contact and of an organization show alike: their exchanges, and their links.
 */
final readonly class TimelineResourceFactory
{
    public function __construct(private Links $links, private IntentLinks $intents)
    {
    }

    /**
     * Itself, the next slice, and noting an exchange when the signed-in member may (`IntentLinks`).
     *
     * @param array{string, string} $routes  the route of the timeline and the route noting an exchange in it
     * @param array<string, string> $subject the parameters naming whose timeline it is
     */
    public function createLinks(array $routes, array $subject, Timeline $response): TimelineLinksResource
    {
        [$timeline, $noteExchange] = $routes;

        return new TimelineLinksResource(
            $this->links->to($timeline, $subject),
            null === $response->next ? null : $this->links->to($timeline, $subject + ['after' => $response->next]),
            $this->intents->shows('NoteExchange', $response->possibleIntents, User::PERSONAL_DATA) ? $this->links->to($noteExchange, $subject) : null,
        );
    }

    public function createExchange(ExchangeInTimeline $exchange): ExchangeResource
    {
        return new ExchangeResource(
            id: $exchange->id,
            channel: $exchange->channel,
            direction: $exchange->direction,
            missed: $exchange->missed,
            occurredAt: $exchange->occurredAt->format(\DateTimeInterface::ATOM),
            text: $exchange->text,
            textFlagged: $exchange->textFlagged,
            textToMakeConform: $exchange->textToMakeConform,
            toMakeConform: $exchange->toMakeConform,
            organizationIds: $exchange->organizationIds,
            attachmentCount: $exchange->attachmentCount,
            links: new ExchangeLinksResource($this->links->to('api_exchanges_show', ['id' => $exchange->id])),
        );
    }
}
