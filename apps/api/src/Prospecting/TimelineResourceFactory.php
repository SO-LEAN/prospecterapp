<?php

namespace App\Prospecting;

use App\Prospecting\Resource\ExchangeLinksResource;
use App\Prospecting\Resource\ExchangeResource;
use App\Prospecting\Resource\TimelineLinksResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\Prospecting\Application\ConsultCalendar\Calendar;
use Prospecter\Core\Prospecting\Application\ConsultWhatIsToProcess\WhatIsToProcess;
use Prospecter\Core\Prospecting\Application\ConsultMyDay\MyDay;
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
     * Itself, the next slice, and noting or planning an exchange when the signed-in member may (`IntentLinks`): every
     * prospector plans, noting needs the right to manage personal data (ADR 0129).
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
            $this->intents->shows('PlanExchange', $response->possibleIntents) ? $this->links->to($planExchange, $subject) : null,
        );
    }

    public function createExchange(ExchangeInTimeline $exchange): ExchangeResource
    {
        return $this->exchangeWith($exchange, new ExchangeLinksResource($this->links->to('api_exchanges_show', ['id' => $exchange->id])));
    }

    /**
     * In the calendar of its holder, it is moved by dragging it when the calendar says it may be.
     */
    public function createCalendarExchange(ExchangeInTimeline $exchange, Calendar $calendar): ExchangeResource
    {
        $move = in_array($exchange->id, $calendar->movable, true) ? $this->links->to('api_exchanges_move', ['id' => $exchange->id]) : null;

        return $this->exchangeWith($exchange, new ExchangeLinksResource($this->links->to('api_exchanges_show', ['id' => $exchange->id]), $move));
    }

    /**
     * In the day of its holder, it is marked done or missed when its time has come.
     */
    public function createDayExchange(ExchangeInTimeline $exchange, MyDay $myDay): ExchangeResource
    {
        $toHandle = in_array($exchange->id, $myDay->toHandle, true);

        return $this->exchangeWith($exchange, new ExchangeLinksResource(
            $this->links->to('api_exchanges_show', ['id' => $exchange->id]),
            markDone: $toHandle ? $this->links->to('api_exchanges_mark_done', ['id' => $exchange->id]) : null,
            markMissed: $toHandle ? $this->links->to('api_exchanges_mark_missed', ['id' => $exchange->id]) : null,
        ));
    }

    /**
     * Among the exchanges its holder has to process, it is handled from there: marked done or missed, moved or
     * cancelled, as its state offers.
     */
    public function createExchangeToProcess(ExchangeInTimeline $exchange, WhatIsToProcess $toProcess): ExchangeResource
    {
        $offered = $toProcess->possibleIntents[$exchange->id] ?? [];
        $link = fn (string $intent, string $route): ?LinkResource => $this->intents->shows($intent, $offered) ? $this->links->to($route, ['id' => $exchange->id]) : null;

        return $this->exchangeWith($exchange, new ExchangeLinksResource(
            $this->links->to('api_exchanges_show', ['id' => $exchange->id]),
            move: $link('MoveExchange', 'api_exchanges_move'),
            markDone: $link('MarkExchangeDone', 'api_exchanges_mark_done'),
            markMissed: $link('MarkExchangeMissed', 'api_exchanges_mark_missed'),
            cancel: $link('CancelExchange', 'api_exchanges_cancel'),
        ));
    }

    private function exchangeWith(ExchangeInTimeline $exchange, ExchangeLinksResource $links): ExchangeResource
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
            links: $links,
        );
    }
}
