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
     * The to-dos a member holds still to do, in `GSI2`.
     */
    public static function stillToDoBy(string $member): string
    {
        return 'STILL_TO_DO#'.$member;
    }

    /**
     * The to-dos a member holds, done or not: the PK of their links, and the partition of their calendar in `GSI1`
     * (ADR 0134).
     */
    public static function calendar(string $member): string
    {
        return 'TODO_CALENDAR#'.$member;
    }

    /**
     * Where a to-do stands in a calendar: its due as written, its day, then its hour, none before any, then its id.
     *
     * @param array<string, mixed> $document
     */
    public static function calendarPositionOf(array $document): string
    {
        $due = Typed::document($document['due']);

        return sprintf('%s#%s#%s', Typed::string($due['day']), Typed::stringOrNull($due['time'] ?? null) ?? '', Typed::string($document['id']));
    }

    /**
     * Where a day starts in a calendar, before every to-do due on it.
     */
    public static function dayStart(string $day): string
    {
        return $day;
    }

    /**
     * Where a day ends in a calendar, after every to-do due on it.
     */
    public static function dayEnd(string $day): string
    {
        return $day.'#~';
    }
}
