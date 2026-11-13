<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Policy;

use Prospecter\Core\Prospecting\Domain\Model\ToDoIntent;

/**
 * What the state of a to-do offers now (ADR 0088): the to-do refuses the rest, the reading of a to-do tells it.
 */
final class ToDoPossibilities
{
    /**
     * @return list<ToDoIntent>
     */
    #[\NoDiscard]
    public static function given(bool $done): array
    {
        return array_values(array_filter(ToDoIntent::cases(), static fn (ToDoIntent $intent): bool => match ($intent) {
            ToDoIntent::TickOffToDo => !$done,
            ToDoIntent::UntickToDo => $done,
            default => true,
        }));
    }
}
