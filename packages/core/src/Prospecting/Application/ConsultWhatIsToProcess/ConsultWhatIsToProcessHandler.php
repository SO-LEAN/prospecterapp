<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultWhatIsToProcess;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\HolderIntents;
use Prospecter\Core\Prospecting\Application\Overdue;
use Prospecter\Core\Prospecting\Application\Port\AgendaRetriever;
use Prospecter\Core\Prospecting\Application\Port\ToDoListRetriever;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Application\ToDoList;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultWhatIsToProcessHandler implements QueryHandler
{
    public function __construct(
        private AgendaRetriever $agendaRetriever,
        private ToDoListRetriever $toDoListRetriever,
        private Prospectors $prospectors,
    ) {
    }

    public function __invoke(ConsultWhatIsToProcess $query, ConsultWhatIsToProcessOutput $output): void
    {
        $holder = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));

        $exchanges = $this->endedExchanges($holder, $query->at);
        $toDos = $this->toDosDueBy($holder, $query->at);

        $output->present(new WhatIsToProcess(
            $exchanges,
            HolderIntents::offeredBy($exchanges, $query->at),
            $toDos,
            self::daysOverdue($toDos, $query->at),
        ));
    }

    /**
     * The agenda holds only what is still planned: one started is to process once it has ended.
     *
     * @return list<ExchangeInTimeline>
     */
    private function endedExchanges(Prospector $holder, \DateTimeImmutable $at): array
    {
        $ended = array_filter($this->agendaRetriever->plannedBefore($holder, $at), static fn (ExchangeInTimeline $exchange): bool => Overdue::hasEnded($exchange, $at));

        return array_values($ended);
    }

    /**
     * Those still to do of the day of the reading, whatever their hour, and of the days before: the most overdue first.
     *
     * @return list<ToDoInList>
     */
    private function toDosDueBy(Prospector $holder, \DateTimeImmutable $at): array
    {
        $due = array_filter($this->toDoListRetriever->stillToDoBy($holder->id), static fn (ToDoInList $toDo): bool => Overdue::daysSince($toDo, $at) >= 0);

        return ToDoList::inOrder(array_values($due))->toDos;
    }

    /**
     * @param list<ToDoInList> $toDos
     *
     * @return array<string, int> by to-do id
     */
    private static function daysOverdue(array $toDos, \DateTimeImmutable $at): array
    {
        return array_combine(
            array_map(static fn (ToDoInList $toDo): string => $toDo->id, $toDos),
            array_map(static fn (ToDoInList $toDo): int => Overdue::daysSince($toDo, $at), $toDos),
        );
    }
}
