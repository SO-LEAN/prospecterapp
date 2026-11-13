<?php

namespace Tests\App\Prospecting;

use App\Prospecting\Persistence\DynamoExchangeRepository;
use App\Prospecting\Persistence\ExchangeKeys;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\Port\CalendarRetriever;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The calendar of a member, read from the links `DynamoExchangeRepository` writes with every exchange
 * (`CALENDAR#<member>`): the exchanges of a period, whatever their status, at the moment they happen.
 */
class DynamoCalendarRetrieverTest extends ApiTestCase
{
    private const string NOW = '2026-11-16 12:00 UTC';

    private Prospector $prospector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()), true);
    }

    public function testThePeriodHoldsTheExchangesHappeningInItWhateverTheirStatusByTheirMoment(): void
    {
        $noted = $this->noted('2026-11-16 08:00 UTC');
        $cancelled = $this->planned('2026-11-20 09:00 UTC');
        $cancelled->cancel(null, $this->prospector->id, new \DateTimeImmutable(self::NOW));
        $planned = $this->planned('2026-11-17 13:00 UTC');
        $atTheEnd = $this->planned('2026-11-23 00:00 UTC');
        array_map($this->exchanges()->save(...), [$noted, $cancelled, $planned, $atTheEnd]);

        $this->assertSame([[$noted->id()->value, 'done'], [$planned->id()->value, 'planned'], [$cancelled->id()->value, 'cancelled']], self::described($this->calendarOf($this->prospector, '2026-11-16 00:00 UTC', '2026-11-23 00:00 UTC')));
    }

    public function testAMovedExchangeMovesInTheCalendar(): void
    {
        $exchange = $this->planned('2026-11-17 13:00 UTC');
        $this->exchanges()->save($exchange);

        $exchange->moveTo(new \DateTimeImmutable('2026-11-24 08:00 UTC'), $this->prospector->id, new \DateTimeImmutable(self::NOW));
        $this->exchanges()->save($exchange);

        $this->assertSame([], $this->calendarOf($this->prospector, '2026-11-16 00:00 UTC', '2026-11-23 00:00 UTC'));
        $this->assertSame([[$exchange->id()->value, 'planned']], self::described($this->calendarOf($this->prospector, '2026-11-23 00:00 UTC', '2026-11-30 00:00 UTC')));
    }

    public function testARemovedExchangeLeavesTheCalendar(): void
    {
        $exchange = $this->noted('2026-11-10 08:00 UTC');
        $this->exchanges()->save($exchange);

        $this->exchanges()->remove($exchange);

        $this->assertSame([], $this->calendarOf($this->prospector, '2026-11-09 00:00 UTC', '2026-11-16 00:00 UTC'));
    }

    public function testTheCalendarOfAColleagueHoldsNoneOfMine(): void
    {
        $this->exchanges()->save($this->noted('2026-11-10 08:00 UTC'));

        $this->assertSame([], $this->calendarOf(new Prospector(new MemberId(self::newIdentity()), $this->prospector->accountId, true), '2026-11-09 00:00 UTC', '2026-11-16 00:00 UTC'));
    }

    public function testTheExchangesWrittenBeforeTheCalendarsAreInItOnceTheirKeysAreWrittenAgain(): void
    {
        $exchange = $this->noted('2026-11-10 08:00 UTC');
        $this->exchanges()->save($exchange);
        $table = self::getContainer()->get('app.table.prospecting');
        $this->assertInstanceOf(Table::class, $table);
        $table->batch([], [[ExchangeKeys::calendar($this->prospector->id->value), ExchangeKeys::exchange($exchange->id()->value)]]);
        $this->assertSame([], $this->calendarOf($this->prospector, '2026-11-09 00:00 UTC', '2026-11-16 00:00 UTC'));

        self::getContainer()->get(DynamoExchangeRepository::class)->writeTheKeysAgain();

        $this->assertSame([[$exchange->id()->value, 'done']], self::described($this->calendarOf($this->prospector, '2026-11-09 00:00 UTC', '2026-11-16 00:00 UTC')));
    }

    /**
     * @return list<ExchangeInTimeline>
     */
    private function calendarOf(Prospector $holder, string $from, string $until): array
    {
        return self::getContainer()->get(CalendarRetriever::class)->heldBy($holder, new CalendarPeriod(new \DateTimeImmutable($from), new \DateTimeImmutable($until)));
    }

    /**
     * @param list<ExchangeInTimeline> $exchanges
     *
     * @return list<array{string, string}>
     */
    private static function described(array $exchanges): array
    {
        return array_map(static fn (ExchangeInTimeline $exchange): array => [$exchange->id, $exchange->status], $exchanges);
    }

    private function noted(string $occurredAt): Exchange
    {
        return Exchange::notedWithAContact($this->prospector->accountId, $this->prospector->id, new ExchangeId(self::newIdentity()), new PersonId(self::newIdentity()), [new OrganizationId(self::newIdentity())], new ExchangeFacts(Channel::Call, Direction::Outbound, new \DateTimeImmutable($occurredAt)), ExchangeStatus::Done, new \DateTimeImmutable(self::NOW));
    }

    private function planned(string $plannedAt): Exchange
    {
        return Exchange::plannedWithAContact($this->prospector->accountId, $this->prospector->id, new ExchangeId(self::newIdentity()), new PersonId(self::newIdentity()), [new OrganizationId(self::newIdentity())], ExchangeFacts::planned(Channel::Meeting, new \DateTimeImmutable($plannedAt), Duration::inMinutes(null), null), new \DateTimeImmutable(self::NOW));
    }

    private function exchanges(): ExchangeRepository
    {
        return self::getContainer()->get(ExchangeRepository::class);
    }
}
