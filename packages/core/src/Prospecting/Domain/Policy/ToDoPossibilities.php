<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Policy;

use Prospecter\Core\Prospecting\Domain\Model\ToDoIntent;

/**
 * What a to-do's state offers now (ADR 0088). The to-do refuses the rest, and the to-do's reading tells it.
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
