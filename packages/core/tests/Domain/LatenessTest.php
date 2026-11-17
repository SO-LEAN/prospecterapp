<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Model\Due;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Slot;
use Prospecter\Core\Prospecting\Domain\Policy\Lateness;

final class LatenessTest extends TestCase
{
    private const string READING = '2026-11-13 10:00';

    #[DataProvider('exchanges')]
    public function testAPlannedExchangeWhoseSlotHasEndedIsLate(ExchangeStatus $status, string $start, bool $late): void
    {
        $slot = new Slot(new \DateTimeImmutable($start), Duration::inMinutes(30));

        $this->assertSame($late, Lateness::ofExchange($status, $slot, new \DateTimeImmutable(self::READING)));
    }

    /**
     * @return iterable<string, array{ExchangeStatus, string, bool}>
     */
    public static function exchanges(): iterable
    {
        yield 'planned, its slot ended' => [ExchangeStatus::Planned, '2026-11-12 14:00', true];
        yield 'planned, its slot ending at the reading' => [ExchangeStatus::Planned, '2026-11-13 09:30', true];
        yield 'planned, its slot under way' => [ExchangeStatus::Planned, '2026-11-13 09:45', false];
        yield 'planned, its slot to come' => [ExchangeStatus::Planned, '2026-11-14 14:00', false];
        yield 'done' => [ExchangeStatus::Done, '2026-11-12 14:00', false];
        yield 'missed' => [ExchangeStatus::Missed, '2026-11-12 14:00', false];
        yield 'cancelled' => [ExchangeStatus::Cancelled, '2026-11-12 14:00', false];
    }

    #[DataProvider('toDos')]
    public function testAToDoNotDoneWhoseHourOrElseDayHasPassedIsLate(Due $due, bool $done, bool $late): void
    {
        $this->assertSame($late, Lateness::ofToDo($due, $done, new \DateTimeImmutable(self::READING)));
    }

    /**
     * @return iterable<string, array{Due, bool, bool}>
     */
    public static function toDos(): iterable
    {
        yield 'of a day passed' => [new Due('2026-11-12'), false, true];
        yield 'of the day, without an hour' => [new Due('2026-11-13'), false, false];
        yield 'of the day, its hour passed' => [new Due('2026-11-13', '09:00'), false, true];
        yield 'of the day, its hour to come' => [new Due('2026-11-13', '11:00'), false, false];
        yield 'of a day to come' => [new Due('2026-11-14'), false, false];
        yield 'done, of a day passed' => [new Due('2026-11-12'), true, false];
    }
}
