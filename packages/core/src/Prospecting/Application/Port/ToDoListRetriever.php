<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownToDo;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the to-dos without restoring them (ADR 0088). Each to-do says what its state offers (`ToDoPossibilities`).
 * Only the calendar's to-dos come in an order. `ToDoList` orders the others.
 */
interface ToDoListRetriever
{
    /**
     * @throws UnknownToDo
     */
    public function get(ToDoId $id): ToDoInList;

    /**
     * Returns the account's to-dos for the contact, done or not.
     *
     * @return list<ToDoInList>
     */
    public function forContact(ContactId $contact, AccountId $accountId): array;

    /**
     * Returns the to-dos kept for the organization, done or not. They are for the organization or for its contacts.
     *
     * @return list<ToDoInList>
     */
    public function keptFor(OrganizationId $organization): array;

    /**
     * Returns the to-dos the member holds that are still to do.
     *
     * @return list<ToDoInList>
     */
    public function stillToDoBy(MemberId $holder): array;

    /**
     * Returns the to-dos the member holds that are due on a day of the period, done or not. The days are read on the
     * reader's clock. The to-dos are sorted by their due as written: by day, a to-do without an hour before those of
     * the same day with one, then by id (ADR 0134).
     *
     * @return list<ToDoInList>
     */
    public function dueIn(MemberId $holder, CalendarPeriod $period): array;
}
