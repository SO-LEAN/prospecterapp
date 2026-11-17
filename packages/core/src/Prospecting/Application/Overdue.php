<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

use Prospecter\Core\Prospecting\Domain\Model\Due;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Slot;
use Prospecter\Core\Prospecting\Domain\Policy\Lateness;

/**
 * The read models of the exchanges and the to-dos told in the terms of the domain, asking it what is late.
 */
final class Overdue
{
    /**
     * @param list<ExchangeInTimeline> $exchanges
     * @param list<ToDoInList>         $toDos
     *
     * @return list<string> the ids of those late
     */
    public static function among(array $exchanges, array $toDos, \DateTimeImmutable $at): array
    {
        $lateExchanges = array_filter($exchanges, static fn (ExchangeInTimeline $exchange): bool => self::isLate($exchange, $at));
        $lateToDos = array_filter($toDos, static fn (ToDoInList $toDo): bool => Lateness::ofToDo(self::dueOf($toDo), $toDo->isDone(), $at));

        return [
            ...array_values(array_map(static fn (ExchangeInTimeline $exchange): string => $exchange->id, $lateExchanges)),
            ...array_values(array_map(static fn (ToDoInList $toDo): string => $toDo->id, $lateToDos)),
        ];
    }

    public static function isLate(ExchangeInTimeline $exchange, \DateTimeImmutable $at): bool
    {
        return Lateness::ofExchange(ExchangeStatus::from($exchange->status), self::slotOf($exchange), $at);
    }

    /**
     * The days since the day of the to-do, none on its day, fewer than none before it.
     */
    public static function daysSince(ToDoInList $toDo, \DateTimeImmutable $at): int
    {
        return self::dueOf($toDo)->daysOverdueAt($at);
    }

    private static function slotOf(ExchangeInTimeline $exchange): Slot
    {
        return new Slot($exchange->occurredAt, Duration::inMinutes($exchange->durationInMinutes));
    }

    private static function dueOf(ToDoInList $toDo): Due
    {
        return new Due($toDo->dueOn, $toDo->dueAt);
    }
}
