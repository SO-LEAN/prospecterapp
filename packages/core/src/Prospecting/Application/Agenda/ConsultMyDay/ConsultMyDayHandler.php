<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Agenda\ConsultMyDay;

use Prospecter\Core\Prospecting\Application\Agenda\AgendaInterlocutors;
use Prospecter\Core\Prospecting\Application\Agenda\HolderIntents;
use Prospecter\Core\Prospecting\Application\Port\AgendaRetriever;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class ConsultMyDayHandler implements QueryHandler
{
    public const int NEXT_EXCHANGES = 5;

    public function __construct(
        private AgendaRetriever $agendaRetriever,
        private Prospectors $prospectors,
        private AgendaInterlocutors $agendaInterlocutors,
    ) {
    }

    public function __invoke(ConsultMyDay $query, ConsultMyDayOutput $output): void
    {
        $holder = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $day = new CalendarPeriod($query->from, $query->until);

        $plannedToday = $this->agendaRetriever->plannedIn($holder, $day);
        $plannedNext = $this->agendaRetriever->firstPlannedFrom($holder, $day->until, self::NEXT_EXCHANGES);
        $interlocutors = $this->agendaInterlocutors->of($holder, [...$plannedToday, ...$plannedNext]);
        $today = $interlocutors->known($plannedToday);

        $output->present(new MyDay($today, $interlocutors->known($plannedNext), HolderIntents::offering(ExchangeIntent::MarkExchangeDone, $today, $query->at), $interlocutors));
    }
}
