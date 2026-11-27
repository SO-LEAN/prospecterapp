<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Agenda\ConsultWhatIsToProcess;

use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\ToDoInList;

final readonly class WhatIsToProcess
{
    /**
     * @param list<ExchangeInTimeline>    $exchanges        the exchanges the prospector planned that have ended and
     *                                                      are still planned, the oldest first
     * @param array<string, list<string>> $possibleIntents  by exchange id, the names of what each exchange offers its
     *                                                      holder now: MarkExchangeDone, MarkExchangeMissed,
     *                                                      MoveExchange, CancelExchange and so on
     * @param list<ToDoInList>            $toDos            the to-dos the prospector still has to do, due on the
     *                                                      reading day on their clock or earlier, the most overdue
     *                                                      first
     * @param array<string, int>          $daysOverdue      by to-do id, the days since the to-do's day, none for a to-do
     *                                                      due on the reading day
     * @param list<ContactToAttachInList> $contactsToAttach the contacts who wait for the prospector to attach them, the
     *                                                      next to be erased first (ADR 0146)
     */
    public function __construct(public array $exchanges, public array $possibleIntents, public array $toDos, public array $daysOverdue, public array $contactsToAttach)
    {
    }
}
