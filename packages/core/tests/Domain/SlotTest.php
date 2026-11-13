<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Slot;

final class SlotTest extends TestCase
{
    private const string AFTERNOON = '2026-11-17 14:00';

    #[DataProvider('slots')]
    public function testOverlapsAnotherWhenTheyShareAMoment(string $start, int $minutes, bool $overlaps): void
    {
        $slot = self::slot(self::AFTERNOON, 30);
        $other = self::slot($start, $minutes);

        $this->assertSame([$overlaps, $overlaps], [$slot->overlaps($other), $other->overlaps($slot)]);
    }

    /**
     * @return iterable<string, array{string, int, bool}>
     */
    public static function slots(): iterable
    {
        yield 'the same' => [self::AFTERNOON, 30, true];
        yield 'starting inside' => ['2026-11-17 14:15', 30, true];
        yield 'around it' => ['2026-11-17 13:00', 120, true];
        yield 'inside it' => ['2026-11-17 14:10', 5, true];
        yield 'ending when it starts' => ['2026-11-17 13:30', 30, false];
        yield 'starting when it ends' => ['2026-11-17 14:30', 30, false];
        yield 'the next day' => ['2026-11-18 14:00', 30, false];
    }

    public function testNoSlotStartingEarlierThanTheLongestDurationOverlapsIt(): void
    {
        $slot = self::slot(self::AFTERNOON, 30);
        $earliest = $slot->earliestStartOfAnOverlap();

        $this->assertSame([false, true], [new Slot($earliest, Duration::inMinutes(Duration::LONGEST))->overlaps($slot), new Slot($earliest->modify('+1 minute'), Duration::inMinutes(Duration::LONGEST))->overlaps($slot)]);
    }

    #[DataProvider('channels')]
    public function testOnlyACallAMeetingOrAVideoCallIsPlanned(Channel $channel, bool $planned): void
    {
        $this->assertSame($planned, $channel->mayBePlanned());
    }

    /**
     * @return iterable<string, array{Channel, bool}>
     */
    public static function channels(): iterable
    {
        foreach (Channel::cases() as $channel) {
            yield $channel->name => [$channel, in_array($channel, [Channel::Call, Channel::Meeting, Channel::VideoCall], true)];
        }
    }

    private static function slot(string $start, int $minutes): Slot
    {
        return new Slot(new \DateTimeImmutable($start), Duration::inMinutes($minutes));
    }
}
