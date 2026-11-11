<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Reporting\Domain\Model\Month;

class MonthTest extends TestCase
{
    #[DataProvider('moments')]
    public function testAMomentFallsInItsMonthInUtc(string $moment, string $month): void
    {
        $this->assertSame($month, Month::of(new \DateTimeImmutable($moment))->value);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function moments(): iterable
    {
        yield 'in UTC' => ['2026-10-31 23:59:59 UTC', '2026-10'];
        yield 'the 1st of November in Paris, October in UTC' => ['2026-11-01 00:30 Europe/Paris', '2026-10'];
        yield 'the 31st of October in New York, November in UTC' => ['2026-10-31 21:00 America/New_York', '2026-11'];
    }

    #[DataProvider('notMonths')]
    public function testAMonthIsWrittenYearDashMonth(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Month($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notMonths(): iterable
    {
        yield 'a day' => ['2026-10-31'];
        yield 'the 13th month' => ['2026-13'];
        yield 'the month 0' => ['2026-00'];
        yield 'a month alone' => ['10'];
    }
}
