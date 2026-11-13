<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Exception\CalendarPeriodReversed;
use Prospecter\Core\Prospecting\Domain\Exception\CalendarPeriodTooLong;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;

final class CalendarPeriodTest extends TestCase
{
    #[DataProvider('readAtOnce')]
    public function testSixWeeksAtMost(string $from, string $until): void
    {
        $period = new CalendarPeriod(new \DateTimeImmutable($from), new \DateTimeImmutable($until));

        $this->assertEquals(new \DateTimeImmutable($until), $period->until);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function readAtOnce(): iterable
    {
        yield 'a day' => ['2026-11-17T00:00:00+01:00', '2026-11-18T00:00:00+01:00'];
        yield 'six weeks' => ['2026-10-26T00:00:00+01:00', '2026-12-07T00:00:00+01:00'];
        yield 'six weeks gaining an hour at the change of time' => ['2026-09-28T00:00:00+02:00', '2026-11-09T00:00:00+01:00'];
    }

    #[DataProvider('tooLong')]
    public function testNeverLonger(string $from, string $until): void
    {
        $this->expectException(CalendarPeriodTooLong::class);

        new CalendarPeriod(new \DateTimeImmutable($from), new \DateTimeImmutable($until));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function tooLong(): iterable
    {
        yield 'six weeks and a minute' => ['2026-10-26T00:00:00+01:00', '2026-12-07T00:01:00+01:00'];
        yield 'six weeks and a day losing an hour at the change of time' => ['2027-03-01T00:00:00+01:00', '2027-04-13T00:00:00+02:00'];
    }

    #[DataProvider('reversed')]
    public function testItEndsAfterItStarts(string $until): void
    {
        $this->expectException(CalendarPeriodReversed::class);

        new CalendarPeriod(new \DateTimeImmutable('2026-11-17T00:00:00+01:00'), new \DateTimeImmutable($until));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function reversed(): iterable
    {
        yield 'when it starts' => ['2026-11-17T00:00:00+01:00'];
        yield 'before' => ['2026-11-16T00:00:00+01:00'];
    }
}
