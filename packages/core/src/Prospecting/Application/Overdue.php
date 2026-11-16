<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

use Prospecter\Core\Prospecting\Domain\Model\Due;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Slot;

/**
 * What a prospector let pass at the moment of a reading, on their clock: a planned exchange whose slot has ended,
 * still to mark done, missed or cancelled; a to-do not done whose hour, or else whose day, has passed.
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
        $lateExchanges = array_filter($exchanges, static fn (ExchangeInTimeline $exchange): bool => self::isLateExchange($exchange, $at));
        $lateToDos = array_filter($toDos, static fn (ToDoInList $toDo): bool => !$toDo->isDone() && self::dueOf($toDo)->hasPassedBy($at));

        return [
            ...array_values(array_map(static fn (ExchangeInTimeline $exchange): string => $exchange->id, $lateExchanges)),
            ...array_values(array_map(static fn (ToDoInList $toDo): string => $toDo->id, $lateToDos)),
        ];
    }

    /**
     * Its slot has ended, whatever its status.
     */
    public static function hasEnded(ExchangeInTimeline $exchange, \DateTimeImmutable $at): bool
    {
        return new Slot($exchange->occurredAt, Duration::inMinutes($exchange->durationInMinutes))->hasEndedBy($at);
    }

    /**
     * The days since the day of the to-do, none on its day, fewer than none before it.
     */
    public static function daysSince(ToDoInList $toDo, \DateTimeImmutable $at): int
    {
        return self::dueOf($toDo)->daysOverdueAt($at);
    }

    private static function isLateExchange(ExchangeInTimeline $exchange, \DateTimeImmutable $at): bool
    {
        return ExchangeStatus::Planned->value === $exchange->status && self::hasEnded($exchange, $at);
    }

    private static function dueOf(ToDoInList $toDo): Due
    {
        return new Due($toDo->dueOn, $toDo->dueAt);
    }
}
