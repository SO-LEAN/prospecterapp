<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

use Prospecter\Core\Prospecting\Domain\Model\Due;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Slot;
use Prospecter\Core\Prospecting\Domain\Rule\Lateness;

/**
 * Translates the exchanges' and the to-dos' read models into the domain's terms, and asks the domain what is late.
 */
final class Overdue
{
    /**
     * @param list<ExchangeInTimeline> $exchanges
     * @param list<ToDoInList>         $toDos
     *
     * @return list<string> the ids of the late ones
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
     * The number of days since the to-do's day. It is zero on that day, and below zero before it.
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
