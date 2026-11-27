<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\MyDayLinksResource;
use App\Prospecting\Resource\ExchangeResource;
use App\Prospecting\TimelineResourceFactory;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultMyDay\ConsultMyDayOutput;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultMyDay\MyDay;

/**
 * Presents what Prospecting shows of the signed-in member's day: each exchange with its links, and who it is with, by
 * ids. One presenter serves one call (ADR 0085, 0217).
 */
final class MyDayPresenter implements ConsultMyDayOutput
{
    public private(set) MyDay $myDay;

    /** @var array<string, ExchangeResource> by exchange id, today's and the next ones */
    public private(set) array $exchanges = [];

    public function __construct(
        private readonly TimelineResourceFactory $timelineResourceFactory,
        public readonly MyDayLinksResource $links,
    ) {
    }

    public function present(MyDay $myDay): void
    {
        $this->myDay = $myDay;

        foreach ([...$myDay->today, ...$myDay->next] as $exchange) {
            $this->exchanges[$exchange->id] = $this->timelineResourceFactory->createDayExchange($exchange, $myDay);
        }
    }
}
