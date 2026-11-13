<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Exception\DurationOutOfRange;
use Prospecter\Core\Prospecting\Domain\Model\Duration;

final class DurationTest extends TestCase
{
    public function testHalfAnHourWhenNotSaid(): void
    {
        $this->assertSame(30, Duration::inMinutes(null)->minutes);
    }

    #[DataProvider('bounds')]
    public function testFromFiveMinutesToEightHours(int $minutes): void
    {
        $this->assertSame($minutes, Duration::inMinutes($minutes)->minutes);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function bounds(): iterable
    {
        yield 'five minutes' => [5];
        yield 'eight hours' => [480];
    }

    #[DataProvider('outOfRange')]
    public function testNeitherShorterNorLonger(int $minutes): void
    {
        $this->expectException(DurationOutOfRange::class);

        Duration::inMinutes($minutes);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function outOfRange(): iterable
    {
        yield 'four minutes' => [4];
        yield 'eight hours and a minute' => [481];
    }

    public function testItEndsThatLongAfterItsStart(): void
    {
        $this->assertEquals(new \DateTimeImmutable('2026-11-17 15:15'), Duration::inMinutes(75)->endOf(new \DateTimeImmutable('2026-11-17 14:00')));
    }
}
