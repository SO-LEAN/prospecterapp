<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Booking\Domain\Model\Availability;
use Prospecter\Core\Booking\Domain\Model\BookableDay;
use Prospecter\Core\Booking\Domain\Model\BusyPeriod;
use Prospecter\Core\Booking\Domain\Model\HostTimeZone;
use Prospecter\Core\Booking\Domain\Model\InactivePeriod;
use Prospecter\Core\Booking\Domain\Model\InactivePeriodId;
use Prospecter\Core\Booking\Domain\Model\WallClockTime;
use Prospecter\Core\Booking\Domain\Model\Weekday;
use Prospecter\Core\Booking\Domain\Model\WeeklyWindows;
use Prospecter\Core\Booking\Domain\Model\Window;
use Prospecter\Core\Booking\Domain\Rule\FreeSlots;

/**
 * A host's free slots: their windows cut into 15-minute slots, for 14 days from tomorrow on their clock.
 */
final class FreeSlotsTest extends TestCase
{
    private const string MONDAY_MORNING = '2026-11-23 10:00 Europe/Paris';
    private const string PERIOD = '0190a1c0-0000-7000-8000-000000000003';

    public function testAWindowOfTomorrowIsCutIntoSlotsOf15Minutes(): void
    {
        $days = FreeSlots::given($this->availability(self::window(Weekday::Tuesday, '09:00', '09:30')), [], new \DateTimeImmutable(self::MONDAY_MORNING));

        $this->assertSame(['2026-11-24T09:00:00+01:00', '2026-11-24T09:15:00+01:00'], self::slotsOf($days[0]));
    }

    public function testTheDaysGoOnFor14DaysFromTomorrow(): void
    {
        $days = FreeSlots::given($this->availability(), [], new \DateTimeImmutable(self::MONDAY_MORNING));

        $this->assertSame(['2026-11-24', '2026-11-25', '2026-11-26', '2026-11-27', '2026-11-28', '2026-11-29', '2026-11-30', '2026-12-01', '2026-12-02', '2026-12-03', '2026-12-04', '2026-12-05', '2026-12-06', '2026-12-07'], array_map(static fn (BookableDay $day): string => $day->day, $days));
    }

    public function testTomorrowIsTheDayAfterTheReadingOnTheClockOfTheHost(): void
    {
        $days = FreeSlots::given($this->availability(), [], new \DateTimeImmutable('2026-11-23 23:30 UTC'));

        $this->assertSame('2026-11-25', $days[0]->day);
    }

    public function testNoSlotOnADayOfAnInactivePeriod(): void
    {
        $availability = new Availability(new WeeklyWindows(self::window(Weekday::Tuesday, '09:00', '09:30')), new HostTimeZone('Europe/Paris'), [new InactivePeriod(new InactivePeriodId(self::PERIOD), '2026-11-24', '2026-11-24')]);

        $days = FreeSlots::given($availability, [], new \DateTimeImmutable(self::MONDAY_MORNING));

        $this->assertSame([], $days[0]->slots);
    }

    public function testASlotOverlappingABusyPeriodIsNotFree(): void
    {
        $busy = new BusyPeriod(new \DateTimeImmutable('2026-11-24 09:10 Europe/Paris'), 10);

        $days = FreeSlots::given($this->availability(self::window(Weekday::Tuesday, '09:00', '09:45')), [$busy], new \DateTimeImmutable(self::MONDAY_MORNING));

        $this->assertSame(['2026-11-24T09:30:00+01:00'], self::slotsOf($days[0]));
    }

    public function testABusyPeriodEndingWhenASlotStartsLeavesItFree(): void
    {
        $busy = new BusyPeriod(new \DateTimeImmutable('2026-11-24 08:45 Europe/Paris'), 15);

        $days = FreeSlots::given($this->availability(self::window(Weekday::Tuesday, '09:00', '09:15')), [$busy], new \DateTimeImmutable(self::MONDAY_MORNING));

        $this->assertSame(['2026-11-24T09:00:00+01:00'], self::slotsOf($days[0]));
    }

    public function testABusyPeriodStartingWhenASlotEndsLeavesItFree(): void
    {
        $busy = new BusyPeriod(new \DateTimeImmutable('2026-11-24 09:15 Europe/Paris'), 15);

        $days = FreeSlots::given($this->availability(self::window(Weekday::Tuesday, '09:00', '09:15')), [$busy], new \DateTimeImmutable(self::MONDAY_MORNING));

        $this->assertSame(['2026-11-24T09:00:00+01:00'], self::slotsOf($days[0]));
    }

    public function testABusyPeriodRunningPastMidnightTakesTheFirstSlotsOfTheDay(): void
    {
        $busy = new BusyPeriod(new \DateTimeImmutable('2026-11-23 23:30 Europe/Paris'), 60);

        $days = FreeSlots::given($this->availability(self::window(Weekday::Tuesday, '00:00', '00:45')), [$busy], new \DateTimeImmutable(self::MONDAY_MORNING));

        $this->assertSame(['2026-11-24T00:30:00+01:00'], self::slotsOf($days[0]));
    }

    public function testTheSlotsFollowTheClockOfTheHostOnTheDayItChangesTime(): void
    {
        $days = FreeSlots::given($this->availability(self::window(Weekday::Sunday, '03:00', '03:15')), [], new \DateTimeImmutable('2027-03-27 10:00 Europe/Paris'));

        $this->assertSame(['2027-03-28T03:00:00+02:00'], self::slotsOf($days[0]));
    }

    private function availability(Window ...$windows): Availability
    {
        return new Availability(new WeeklyWindows(...$windows), new HostTimeZone('Europe/Paris'), []);
    }

    private static function window(Weekday $weekday, string $from, string $to): Window
    {
        return new Window($weekday, WallClockTime::at($from, 'from'), WallClockTime::at($to, 'to'));
    }

    /**
     * @return list<string>
     */
    private static function slotsOf(BookableDay $day): array
    {
        return array_map(static fn (\DateTimeImmutable $slot): string => $slot->format(\DATE_ATOM), $day->slots);
    }
}
