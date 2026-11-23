<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Exception\InvalidDue;
use Prospecter\Core\Prospecting\Domain\Model\Due;

final class DueTest extends TestCase
{
    #[DataProvider('dues')]
    public function testADayAndAnHourOfItWhenSaid(string $day, ?string $time): void
    {
        $due = new Due($day, $time);

        $this->assertSame([$day, $time], [$due->day, $due->time]);
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function dues(): iterable
    {
        yield 'a day' => ['2026-11-14', null];
        yield 'its first minute' => ['2026-11-14', '00:00'];
        yield 'its last minute' => ['2026-11-14', '23:59'];
        yield 'a leap day' => ['2028-02-29', '08:30'];
    }

    #[DataProvider('noDays')]
    public function testOnADayOfTheCalendar(string $day): void
    {
        $this->expectExceptionObject(InvalidDue::because('A to-do must be due on a valid day', 'dueOn'));

        new Due($day);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function noDays(): iterable
    {
        yield 'nothing' => [''];
        yield 'written the French way' => ['14/11/2026'];
        yield 'a day of no month' => ['2026-11-31'];
        yield 'no leap year' => ['2026-02-29'];
        yield 'a moment' => ['2026-11-14 08:30'];
    }

    #[DataProvider('noHours')]
    public function testAtAnHourOfItsDay(string $time): void
    {
        $this->expectExceptionObject(InvalidDue::because('A to-do must be due at a valid hour of its day', 'dueAt'));

        new Due('2026-11-14', $time);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function noHours(): iterable
    {
        yield 'nothing' => [''];
        yield 'written the French way' => ['8h30'];
        yield 'without its leading zero' => ['8:30'];
        yield 'past the day' => ['24:00'];
        yield 'past the hour' => ['08:60'];
        yield 'with seconds' => ['08:30:00'];
    }

    #[DataProvider('passed')]
    public function testItHasPassedOnTheClockOfItsReader(?string $time, string $moment, bool $passed): void
    {
        $this->assertSame($passed, new Due('2026-11-13', $time)->hasPassedBy(new \DateTimeImmutable($moment)));
    }

    /**
     * @return iterable<string, array{?string, string, bool}>
     */
    public static function passed(): iterable
    {
        yield 'a day, the day before' => [null, '2026-11-12T23:59:00+01:00', false];
        yield 'a day, all along it' => [null, '2026-11-13T23:59:00+01:00', false];
        yield 'a day, the day after' => [null, '2026-11-14T00:00:00+01:00', true];
        yield 'a day, read elsewhere still on it' => [null, '2026-11-13T18:00:00-05:00', false];
        yield 'an hour, before it' => ['09:00', '2026-11-13T08:59:00+01:00', false];
        yield 'an hour, at it' => ['09:00', '2026-11-13T09:00:00+01:00', false];
        yield 'an hour, after it' => ['09:00', '2026-11-13T09:01:00+01:00', true];
        yield 'an hour, read in New York before it, after it in Paris' => ['09:00', '2026-11-13T08:30:00-05:00', false];
        yield 'an hour, the day after' => ['23:00', '2026-11-14T00:00:00+01:00', true];
    }

    #[DataProvider('overdue')]
    public function testTheDaysSinceItsDayOnTheClockOfItsReader(string $moment, int $days): void
    {
        $this->assertSame($days, new Due('2026-11-13', '18:00')->daysOverdueAt(new \DateTimeImmutable($moment)));
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function overdue(): iterable
    {
        yield 'the day before' => ['2026-11-12T10:00:00+01:00', -1];
        yield 'its morning' => ['2026-11-13T00:00:00+01:00', 0];
        yield 'its evening' => ['2026-11-13T23:59:00+01:00', 0];
        yield 'the day after' => ['2026-11-14T00:00:00+01:00', 1];
        yield 'a month after' => ['2026-12-13T10:00:00+01:00', 30];
        yield 'over a change of time' => ['2027-04-13T10:00:00+02:00', 151];
        yield 'still its day for a reader behind' => ['2026-11-14T03:00:00+01:00', 1];
        yield 'still its day where it is read' => ['2026-11-13T21:00:00-05:00', 0];
    }
}
