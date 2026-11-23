<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Typed;

final class ToDoKeys
{
    public const string TO_DO = 'TODO';

    public static function toDo(string $id): string
    {
        return self::TO_DO.'#'.$id;
    }

    public static function keptFor(string $organization): string
    {
        return 'TODOS#'.$organization;
    }

    /**
     * The to-dos for a contact, in `GSI1`.
     */
    public static function ofContact(string $accountId, string $contact): string
    {
        return sprintf('CONTACT_TODOS#%s#%s', $accountId, $contact);
    }

    /**
     * The to-dos a member holds and still has to do, in `GSI2`.
     */
    public static function stillToDoBy(string $member): string
    {
        return 'STILL_TO_DO#'.$member;
    }

    /**
     * The to-dos a member holds, done or not: their links' PK, and their calendar's partition in `GSI1` (ADR 0134).
     */
    public static function calendar(string $member): string
    {
        return 'TODO_CALENDAR#'.$member;
    }

    /**
     * A to-do's position in a calendar: its due date as written, then its id. The due date is its day, then its hour. A
     * to-do without an hour comes before those with one.
     *
     * @param array<string, mixed> $document
     */
    public static function calendarPositionOf(array $document): string
    {
        $due = Typed::document($document['due']);

        return sprintf('%s#%s#%s', Typed::string($due['day']), Typed::stringOrNull($due['time'] ?? null) ?? '', Typed::string($document['id']));
    }

    /**
     * A day's start in a calendar, before every to-do due on that day.
     */
    public static function dayStart(string $day): string
    {
        return $day;
    }

    /**
     * A day's end in a calendar, after every to-do due on that day.
     */
    public static function dayEnd(string $day): string
    {
        return $day.'#~';
    }
}
