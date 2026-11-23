<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultMyDay;

use Prospecter\Core\Prospecting\Application\HolderIntents;
use Prospecter\Core\Prospecting\Application\Port\AgendaRetriever;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class ConsultMyDayHandler implements QueryHandler
{
    public const int NEXT_EXCHANGES = 5;

    public function __construct(
        private AgendaRetriever $agendaRetriever,
        private Prospectors $prospectors,
    ) {
    }

    public function __invoke(ConsultMyDay $query, ConsultMyDayOutput $output): void
    {
        $holder = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $day = new CalendarPeriod($query->from, $query->until);

        $today = $this->agendaRetriever->plannedIn($holder, $day);
        $next = $this->agendaRetriever->firstPlannedFrom($holder, $day->until, self::NEXT_EXCHANGES);

        $output->present(new MyDay($today, $next, HolderIntents::offering(ExchangeIntent::MarkExchangeDone, $today, $query->at)));
    }
}
