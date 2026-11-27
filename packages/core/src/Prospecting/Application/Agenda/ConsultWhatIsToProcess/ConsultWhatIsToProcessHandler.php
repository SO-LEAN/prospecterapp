<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Agenda\ConsultWhatIsToProcess;

use Prospecter\Core\Prospecting\Application\Agenda\AgendaInterlocutors;
use Prospecter\Core\Prospecting\Application\Agenda\HolderIntents;
use Prospecter\Core\Prospecting\Application\Agenda\Overdue;
use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\Port\AgendaRetriever;
use Prospecter\Core\Prospecting\Application\Port\ContactsToAttachRetriever;
use Prospecter\Core\Prospecting\Application\Port\ToDoListRetriever;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Application\ToDoList;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Port\KnownContacts;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class ConsultWhatIsToProcessHandler implements QueryHandler
{
    public function __construct(
        private AgendaRetriever $agendaRetriever,
        private ToDoListRetriever $toDoListRetriever,
        private Prospectors $prospectors,
        private ContactsToAttachRetriever $contactsToAttachRetriever,
        private AgendaInterlocutors $agendaInterlocutors,
        private KnownContacts $knownContacts,
    ) {
    }

    public function __invoke(ConsultWhatIsToProcess $query, ConsultWhatIsToProcessOutput $output): void
    {
        $holder = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));

        $late = $this->lateExchanges($holder, $query->at);
        $toDos = $this->toDosDueBy($holder, $query->at);
        $contactsToAttach = $this->stillContacts($this->contactsToAttachRetriever->heldBy($holder->id), $holder);
        $interlocutors = $this->agendaInterlocutors->of($holder, $late, $toDos, $contactsToAttach);
        $exchanges = $interlocutors->known($late);

        $output->present(new WhatIsToProcess(
            $exchanges,
            HolderIntents::offeredBy($exchanges, $query->at),
            $toDos,
            self::daysOverdue($toDos, $query->at),
            $contactsToAttach,
            $interlocutors,
        ));
    }

    /**
     * The exchanges the prospector planned and let pass. An exchange in progress needs processing only once it is late.
     *
     * @return list<ExchangeInTimeline>
     */
    private function lateExchanges(Prospector $holder, \DateTimeImmutable $at): array
    {
        $late = array_filter($this->agendaRetriever->plannedBefore($holder, $at), static fn (ExchangeInTimeline $exchange): bool => Overdue::isLate($exchange, $at));

        return array_values($late);
    }

    /**
     * The to-dos still to do that are due on the reading day, whatever their hour, or on an earlier day. The most
     * overdue comes first.
     *
     * @return list<ToDoInList>
     */
    private function toDosDueBy(Prospector $holder, \DateTimeImmutable $at): array
    {
        $due = array_filter($this->toDoListRetriever->stillToDoBy($holder->id), static fn (ToDoInList $toDo): bool => Overdue::daysSince($toDo, $at) >= 0);

        return ToDoList::inOrder(array_values($due))->toDos;
    }

    /**
     * Erasing a contact to attach forgets them in PersonalData at once, and in Prospecting once the reaction follows.
     * Until then, they are no longer to attach.
     *
     * @param list<ContactToAttachInList> $contacts
     *
     * @return list<ContactToAttachInList>
     */
    private function stillContacts(array $contacts, Prospector $holder): array
    {
        $known = array_map(static fn (ContactId $contact): string => $contact->value, $this->knownContacts->whichAreContactsOf(array_map(static fn (ContactToAttachInList $contact): ContactId => new ContactId($contact->personId), $contacts), $holder->accountId));

        return array_values(array_filter($contacts, static fn (ContactToAttachInList $contact): bool => in_array($contact->personId, $known, true)));
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
