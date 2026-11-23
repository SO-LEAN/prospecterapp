<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Booking\Domain\Exception\InvalidBookingCode;
use Prospecter\Core\Booking\Domain\Model\BookingCode;
use Prospecter\Core\Booking\Domain\Model\Channel;
use Prospecter\Core\Booking\Domain\Model\HostTimeZone;
use Prospecter\Core\Booking\Domain\Model\InactivePeriod;
use Prospecter\Core\Booking\Domain\Model\InactivePeriodId;
use Prospecter\Core\Booking\Domain\Model\OfferedChannels;
use Prospecter\Core\Booking\Domain\Model\WallClockTime;
use Prospecter\Core\Booking\Domain\Model\Weekday;
use Prospecter\Core\Booking\Domain\Model\WeeklyWindows;
use Prospecter\Core\Booking\Domain\Model\Window;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The values a host sets on their booking page: times, windows, days, channels and the link's code.
 */
final class BookingSettingsTest extends TestCase
{
    private const string PERIOD = '0190a1c0-0000-7000-8000-000000000003';

    #[DataProvider('times')]
    public function testATimeIsReadAsWritten(string $time, int $minutes): void
    {
        $read = WallClockTime::at($time, 'from');

        $this->assertSame($minutes, $read->minutesOfTheDay);
        $this->assertSame($time, $read->text());
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function times(): iterable
    {
        yield 'midnight' => ['00:00', 0];
        yield 'morning' => ['09:05', 545];
        yield 'last minute' => ['23:59', 1439];
    }

    #[DataProvider('notTimes')]
    public function testATimeIsWrittenHoursAndMinutes(string $time): void
    {
        $this->expectException(BusinessRuleViolation::class);
        $this->expectExceptionMessageIsOrContains('A time is written HH:MM');
        WallClockTime::at($time, 'from');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notTimes(): iterable
    {
        yield 'one digit' => ['9:00'];
        yield 'past the day' => ['24:00'];
        yield 'past the hour' => ['12:60'];
        yield 'French' => ['14h00'];
        yield 'with more after' => ['14:00:00'];
    }

    public function testAWindowOfExactlyOneSlotIsKept(): void
    {
        $window = new Window(Weekday::Monday, WallClockTime::at('14:00', 'from'), WallClockTime::at('14:15', 'to'));

        $this->assertSame('14:15', $window->to->text());
    }

    public function testAWindowEndingOffTheQuarterHourIsRefused(): void
    {
        $this->expectExceptionMessageIsOrContains('A window starts and ends on a quarter hour');
        new Window(Weekday::Monday, WallClockTime::at('14:00', 'from'), WallClockTime::at('15:20', 'to'));
    }

    public function testTheWindowsOfTheWeekAreMondayFirst(): void
    {
        $windows = new WeeklyWindows(self::windowOn(Weekday::Sunday), self::windowOn(Weekday::Monday), self::windowOn(Weekday::Thursday));

        $this->assertSame([Weekday::Monday, Weekday::Thursday, Weekday::Sunday], array_map(static fn (Window $window): Weekday => $window->weekday, $windows->windows));
    }

    public function testAnInactivePeriodCoversItsDaysBoundsIncluded(): void
    {
        $period = new InactivePeriod(new InactivePeriodId(self::PERIOD), '2026-12-21', '2027-01-04');

        $this->assertSame([false, true, true, true, false], array_map($period->covers(...), ['2026-12-20', '2026-12-21', '2026-12-31', '2027-01-04', '2027-01-05']));
    }

    public function testAnInactivePeriodHasEndedOnceItsLastDayIsPast(): void
    {
        $period = new InactivePeriod(new InactivePeriodId(self::PERIOD), '2026-12-21', '2027-01-04');

        $this->assertSame([false, true], [$period->hasEndedBefore('2027-01-04'), $period->hasEndedBefore('2027-01-05')]);
    }

    #[DataProvider('notDays')]
    public function testAnInactivePeriodIsOfDaysWrittenYearMonthDay(string $day): void
    {
        $this->expectExceptionMessageIsOrContains('A day is written YYYY-MM-DD');
        new InactivePeriod(new InactivePeriodId(self::PERIOD), $day, '2027-01-04');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notDays(): iterable
    {
        yield 'French' => ['21/12/2026'];
        yield 'no such day' => ['2026-02-30'];
        yield 'a moment' => ['2026-12-21 10:00'];
    }

    /**
     * A browser may still send a deprecated name from the time zone database. PHP knows these names.
     */
    public function testAFormerNameOfATimeZoneIsKnown(): void
    {
        $this->assertSame('Europe/Kiev', new HostTimeZone('Europe/Kiev')->name);
    }

    public function testTheChannelsAreOfferedOnceEachInTheirOrder(): void
    {
        $this->assertSame([Channel::Call, Channel::VideoCall], new OfferedChannels(Channel::VideoCall, Channel::Call, Channel::VideoCall)->channels);
    }

    #[DataProvider('notCodes')]
    public function testACodeIs22CharactersOfBase62(string $code): void
    {
        $this->expectExceptionObject(InvalidBookingCode::refused());
        new BookingCode($code);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notCodes(): iterable
    {
        yield 'too short' => ['AbCdEfGhIjKlMnOpQrStU'];
        yield 'too long' => ['AbCdEfGhIjKlMnOpQrStUvW'];
        yield 'not base 62' => ['AbCdEfGhIjKlMnOpQrSt-_'];
    }

    private static function windowOn(Weekday $weekday): Window
    {
        return new Window($weekday, WallClockTime::at('09:00', 'from'), WallClockTime::at('12:00', 'to'));
    }
}
