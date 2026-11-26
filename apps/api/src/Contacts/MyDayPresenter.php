<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\DayExchangeResource;
use App\Contacts\Resource\InterlocutorResource;
use App\Contacts\Resource\MyDayLinksResource;
use App\Contacts\Resource\MyDayResource;
use App\Contacts\Resource\OrganizationOfContactResource;
use App\Prospecting\TimelineResourceFactory;
use App\Shared\Links;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultMyDay\ConsultMyDayOutput;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultMyDay\MyDay;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;

/**
 * Presents the signed-in member's day. Each exchange names who it is with, as the query read them: the person and
 * their organization, or the organization itself. Each exchange also gets its links. One presenter serves one call and
 * keeps the resource (ADR 0085, 0217).
 */
final class MyDayPresenter implements ConsultMyDayOutput
{
    public private(set) MyDayResource $resource;

    public function __construct(
        private readonly TimelineResourceFactory $timelineResourceFactory,
        private readonly MyDayLinksResource $linksOfTheDay,
        private readonly Links $links,
    ) {
    }

    public function present(MyDay $myDay): void
    {
        $this->resource = new MyDayResource($this->shown($myDay->today, $myDay), $this->shown($myDay->next, $myDay), $this->linksOfTheDay);
    }

    /**
     * @param list<ExchangeInTimeline> $exchanges
     *
     * @return list<DayExchangeResource>
     */
    private function shown(array $exchanges, MyDay $myDay): array
    {
        return array_map(
            fn (ExchangeInTimeline $exchange): DayExchangeResource => new DayExchangeResource(
                $this->timelineResourceFactory->createDayExchange($exchange, $myDay),
                InterlocutorResource::of($exchange, $myDay->interlocutors),
                OrganizationOfContactResource::keptFor($exchange, $myDay->interlocutors->organizationsOfContacts, $this->links),
            ),
            $exchanges,
        );
    }
}
