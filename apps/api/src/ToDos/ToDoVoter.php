<?php

declare(strict_types=1);

namespace App\ToDos;

use App\Security\AccountResourceVoter;
use Prospecter\Core\Prospecting\Application\ToDoInList;

/**
 * Decides whether the signed-in member can reach a to-do. They reach their own account's to-dos (ADR 0081).
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
