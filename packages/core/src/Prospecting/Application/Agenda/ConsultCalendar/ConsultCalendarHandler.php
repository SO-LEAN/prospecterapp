<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Agenda\ConsultCalendar;

use Prospecter\Core\Prospecting\Application\Agenda\AgendaInterlocutors;
use Prospecter\Core\Prospecting\Application\Agenda\HolderIntents;
use Prospecter\Core\Prospecting\Application\Agenda\Overdue;
use Prospecter\Core\Prospecting\Application\Port\CalendarRetriever;
use Prospecter\Core\Prospecting\Application\Port\ToDoListRetriever;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class ConsultCalendarHandler implements QueryHandler
{
    public function __construct(
        private CalendarRetriever $calendarRetriever,
        private ToDoListRetriever $toDoListRetriever,
        private Prospectors $prospectors,
        private AgendaInterlocutors $agendaInterlocutors,
    ) {
    }

    /**
     * The reader holds every exchange in the calendar. So an exchange offers them what its state offers its holder.
     * The exchanges show at their instant. The to-dos show on their written days, never converted (ADR 0134).
     */
    public function __invoke(ConsultCalendar $query, ConsultCalendarOutput $output): void
    {
        $holder = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $period = new CalendarPeriod($query->from, $query->until);

        $held = $this->calendarRetriever->heldBy($holder, $period);
        $toDos = $this->toDoListRetriever->dueIn($holder->id, $period);
        $interlocutors = $this->agendaInterlocutors->of($holder, $held, $toDos);
        $exchanges = $interlocutors->known($held);

        $output->present(new Calendar(
            $exchanges,
            HolderIntents::offering(ExchangeIntent::MoveExchange, $exchanges, $query->at),
            $toDos,
            Overdue::among($exchanges, $toDos, $query->at),
            $interlocutors,
        ));
    }
}
