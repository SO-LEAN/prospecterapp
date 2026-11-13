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
     * Itself, the next slice, and noting or planning an exchange when the signed-in member may (`IntentLinks`).
     *
     * @param array{string, string, string} $routes  the route of the timeline, the route noting an exchange in it and the route planning one
     * @param array<string, string>         $subject the parameters naming whose timeline it is
     */
    public function createLinks(array $routes, array $subject, Timeline $response): TimelineLinksResource
    {
        [$timeline, $noteExchange, $planExchange] = $routes;

        return new TimelineLinksResource(
            $this->links->to($timeline, $subject),
            null === $response->next ? null : $this->links->to($timeline, $subject + ['after' => $response->next]),
            $this->intents->shows('NoteExchange', $response->possibleIntents, User::PERSONAL_DATA) ? $this->links->to($noteExchange, $subject) : null,
            $this->intents->shows('PlanExchange', $response->possibleIntents, User::PERSONAL_DATA) ? $this->links->to($planExchange, $subject) : null,
        );
    }

    public function createExchange(ExchangeInTimeline $exchange): ExchangeResource
    {
        return new ExchangeResource(
            id: $exchange->id,
            channel: $exchange->channel,
            direction: $exchange->direction,
            occurredAt: $exchange->occurredAt->format(\DateTimeInterface::ATOM),
            status: $exchange->status,
            canceller: $exchange->canceller,
            durationInMinutes: $exchange->durationInMinutes,
            text: $exchange->text,
            place: $exchange->place,
            link: $exchange->link,
            textFlagged: $exchange->textFlagged,
            textToMakeConform: $exchange->textToMakeConform,
            toMakeConform: $exchange->toMakeConform,
            organizationIds: $exchange->organizationIds,
            attachmentCount: $exchange->attachmentCount,
            links: new ExchangeLinksResource($this->links->to('api_exchanges_show', ['id' => $exchange->id])),
        );
    }
}
