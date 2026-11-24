<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\DayExchangeResource;
use App\Contacts\Resource\InterlocutorResource;
use App\Contacts\Resource\MyDayLinksResource;
use App\Contacts\Resource\MyDayResource;
use App\Contacts\Resource\OrganizationOfContactResource;
use App\Prospecting\TimelineResourceFactory;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultMyDay\ConsultMyDayOutput;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultMyDay\MyDay;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * Presents the signed-in member's day. Each exchange names who it is with: the person and their organization, or the
 * organization itself. Each exchange also gets its links. One presenter serves one call. It gets the persons and the
 * organizations the exchanges are with, and keeps the resource (ADR 0085).
 */
final class MyDayPresenter implements ConsultMyDayOutput
{
    public private(set) MyDayResource $resource;

    /**
     * @param array<string, PersonCard>                          $persons                 the exchanges' persons, by id
     * @param array<string, OrganizationSheet>                   $organizations           the organizations that exchanges are held with directly, with no contact, by id
     * @param array<string, list<OrganizationOfContactResource>> $organizationsOfContacts by contact id
     */
    public function __construct(
        private readonly array $persons,
        private readonly array $organizations,
        private readonly TimelineResourceFactory $timelineResourceFactory,
        private readonly MyDayLinksResource $links,
        private readonly array $organizationsOfContacts,
    ) {
    }

    public function present(MyDay $myDay): void
    {
        $this->resource = new MyDayResource($this->shown($myDay->today, $myDay), $this->shown($myDay->next, $myDay), $this->links);
    }

    /**
     * @param list<ExchangeInTimeline> $exchanges
     *
     * @return list<DayExchangeResource>
     */
    private function shown(array $exchanges, MyDay $myDay): array
    {
        return array_map(
            fn (ExchangeInTimeline $exchange): DayExchangeResource => new DayExchangeResource($this->timelineResourceFactory->createDayExchange($exchange, $myDay), InterlocutorResource::of($exchange, $this->persons, $this->organizations), OrganizationOfContactResource::keptFor($exchange, $this->organizationsOfContacts)),
            InterlocutorResource::known($exchanges, $this->organizations),
        );
    }
}
