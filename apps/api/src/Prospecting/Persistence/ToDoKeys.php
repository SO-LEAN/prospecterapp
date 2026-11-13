<?php

namespace App\Prospecting\Persistence;

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
}
