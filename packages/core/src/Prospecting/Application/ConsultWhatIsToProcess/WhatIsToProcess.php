<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultWhatIsToProcess;

use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\ToDoInList;

final readonly class WhatIsToProcess
{
    /**
     * @param list<ExchangeInTimeline>    $exchanges        those the prospector planned, ended and still planned, the
     *                                                      oldest first
     * @param array<string, list<string>> $possibleIntents  by exchange id, the names of what each offers its holder
     *                                                      now: MarkExchangeDone, MarkExchangeMissed, MoveExchange,
     *                                                      CancelExchange...
     * @param list<ToDoInList>            $toDos            those the prospector still has to do, due on the day of the
     *                                                      reading on their clock or before, the most overdue first
     * @param array<string, int>          $daysOverdue      by to-do id, the days since its day, none for one of the day
     * @param list<ContactToAttachInList> $contactsToAttach those waiting for the prospector to attach them, the first
     *                                                      to be erased first (ADR 0146)
     */
    public function __construct(public array $exchanges, public array $possibleIntents, public array $toDos, public array $daysOverdue, public array $contactsToAttach)
    {
    }
}
