<?php

declare(strict_types=1);

namespace App\Prospecting;

use App\Prospecting\Resource\CampaignOfExchangeResource;
use App\Prospecting\Resource\ExchangeLinksResource;
use App\Prospecting\Resource\ExchangeResource;
use App\Prospecting\Resource\TimelineLinksResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\Prospecting\Application\ConsultCalendar\Calendar;
use Prospecter\Core\Prospecting\Application\ConsultMyDay\MyDay;
use Prospecter\Core\Prospecting\Application\ConsultWhatIsToProcess\WhatIsToProcess;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\Timeline;

/**
 * Creates what a contact's timeline and an organization's timeline show alike: their exchanges and their links. An
 * exchange's links include its campaign (ADR 0152).
 */
final readonly class TimelineResourceFactory
{
    public function __construct(private Links $links, private IntentLinks $intents)
    {
    }

    /**
     * Links the timeline itself and its next slice. It also links logging and planning an exchange when the signed-in
     * member may do so (`IntentLinks`). Every prospector plans. Logging needs the right to manage personal data (ADR
     * 0129).
     *
     * @param array{string, string, string} $routes  the timeline's route, the route that logs an exchange in it and the route that plans one
     * @param array<string, string>         $subject the parameters that name whose timeline it is
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
        return $this->exchangeWith($exchange, new ExchangeLinksResource($this->links->to('api_exchanges_show', ['id' => $exchange->id]), campaign: $this->campaignOf($exchange)));
    }

    /**
     * In its holder's calendar, the holder moves an exchange by dragging it, when the calendar allows it.
     */
    public function createCalendarExchange(ExchangeInTimeline $exchange, Calendar $calendar): ExchangeResource
    {
        $move = in_array($exchange->id, $calendar->movable, true) ? $this->links->to('api_exchanges_move', ['id' => $exchange->id]) : null;

        return $this->exchangeWith($exchange, new ExchangeLinksResource($this->links->to('api_exchanges_show', ['id' => $exchange->id]), $move, campaign: $this->campaignOf($exchange)));
    }

    /**
     * In its holder's day, the holder marks an exchange done or missed once its time has come.
     */
    public function createDayExchange(ExchangeInTimeline $exchange, MyDay $myDay): ExchangeResource
    {
        $toHandle = in_array($exchange->id, $myDay->toHandle, true);

        return $this->exchangeWith($exchange, new ExchangeLinksResource(
            $this->links->to('api_exchanges_show', ['id' => $exchange->id]),
            markDone: $toHandle ? $this->links->to('api_exchanges_mark_done', ['id' => $exchange->id]) : null,
            markMissed: $toHandle ? $this->links->to('api_exchanges_mark_missed', ['id' => $exchange->id]) : null,
            campaign: $this->campaignOf($exchange),
        ));
    }

    /**
     * Among the exchanges to process, the holder handles an exchange from there. They mark it done or missed, move it
     * or cancel it, as its state allows.
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
            campaign: $this->campaignOf($exchange),
        ));
    }

    /**
     * Links the campaign the exchange was logged in, while Campaigns still names it. Only members who read the
     * campaigns get the link (ADR 0152).
     */
    private function campaignOf(ExchangeInTimeline $exchange): ?LinkResource
    {
        if (null === $exchange->campaignId || null === $exchange->campaignName || !$this->intents->holds(User::PERSONAL_DATA)) {
            return null;
        }

        return $this->links->to('api_campaigns_show', ['id' => $exchange->campaignId]);
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
            textToRedact: $exchange->textToMakeConform,
            toRedact: $exchange->toMakeConform,
            organizationIds: $exchange->organizationIds,
            attachmentCount: $exchange->attachmentCount,
            campaign: null === $exchange->campaignId ? null : new CampaignOfExchangeResource($exchange->campaignId, $exchange->campaignName),
            contactToAttach: $exchange->withAContactToAttach,
            bookedByLink: $exchange->bookedByLink,
            importedFrom: $exchange->importedFrom,
            createdByAnAI: $exchange->createdByAnAI,
            links: $links,
        );
    }
}
