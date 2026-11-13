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
        $this->expectExceptionObject(InvalidDue::because('A to-do is due on a day', 'dueOn'));

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
        $this->expectExceptionObject(InvalidDue::because('A to-do is due at an hour of its day', 'dueAt'));

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
}
