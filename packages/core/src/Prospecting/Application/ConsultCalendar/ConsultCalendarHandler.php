<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultCalendar;

use Prospecter\Core\Prospecting\Application\HolderIntents;
use Prospecter\Core\Prospecting\Application\Overdue;
use Prospecter\Core\Prospecting\Application\Port\CalendarRetriever;
use Prospecter\Core\Prospecting\Application\Port\ToDoListRetriever;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultCalendarHandler implements QueryHandler
{
    public function __construct(
        private CalendarRetriever $calendarRetriever,
        private ToDoListRetriever $toDoListRetriever,
        private Prospectors $prospectors,
    ) {
    }

    /**
     * Every exchange of the calendar is held by its reader: what it offers them is what its state offers its holder.
     * The exchanges are at their instant, the to-dos on the days written, never converted (ADR 0134).
     */
    public function __invoke(ConsultCalendar $query, ConsultCalendarOutput $output): void
    {
        $holder = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $period = new CalendarPeriod($query->from, $query->until);

        $exchanges = $this->calendarRetriever->heldBy($holder, $period);
        $toDos = $this->toDoListRetriever->dueIn($holder->id, $period);

        $output->present(new Calendar(
            $exchanges,
            HolderIntents::offering(ExchangeIntent::MoveExchange, $exchanges, $query->at),
            $toDos,
            Overdue::among($exchanges, $toDos, $query->at),
        ));
    }
}
