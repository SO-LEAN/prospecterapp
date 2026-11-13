<?php

namespace App\ToDos;

use App\Security\AccountResourceVoter;
use Prospecter\Core\Prospecting\Application\ToDoInList;

/**
 * Whether the signed-in member reaches a to-do: one of their account (ADR 0081).
 *
 * @extends AccountResourceVoter<ToDoInList>
 */
final class ToDoVoter extends AccountResourceVoter
{
    public const string ACCESS = 'TO_DO_ACCESS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ACCESS === $attribute && $subject instanceof ToDoInList;
    }

    protected function accountOf(object $subject): string
    {
        return $subject->accountId;
    }

    protected function unknownReason(): string
    {
        return 'unknownToDo';
    }
}
