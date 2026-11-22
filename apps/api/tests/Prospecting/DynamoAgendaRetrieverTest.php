<?php

namespace Tests\App\Prospecting;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\Port\AgendaRetriever;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Noting;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The agenda of a member: the exchanges they planned, read from the links the repository writes while an exchange
 * is planned, by their moment, the soonest first (ADR 0088).
 */
class DynamoAgendaRetrieverTest extends ApiTestCase
{
    private const string NOW = '2026-11-13 10:00 UTC';

    private Prospector $me;

    protected function setUp(): void
    {
        parent::setUp();
        $this->me = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()), managesPersonalData: true);
    }

    public function testTheExchangesIPlannedStartingWithinTheWindowTheSoonestFirst(): void
    {
        $this->plannedBy($this->me, '2026-11-17 08:00 UTC');
        $later = $this->plannedBy($this->me, '2026-11-17 15:00 UTC');
        $sooner = $this->plannedBy($this->me, '2026-11-17 14:00 UTC');
        $this->plannedBy($this->me, '2026-11-17 16:00 UTC');

        $this->assertSame([self::idOf($sooner), self::idOf($later)], $this->idsInMyAgendaBetween('2026-11-17 08:00 UTC', '2026-11-17 16:00 UTC'));
    }

    public function testTheExchangesIPlannedInADayItsStartIncludedItsEndExcluded(): void
    {
        $atMidnight = $this->plannedBy($this->me, '2026-11-17 00:00 UTC');
        $atNoon = $this->plannedBy($this->me, '2026-11-17 12:00 UTC');
        $this->plannedBy($this->me, '2026-11-18 00:00 UTC');
        $this->plannedBy($this->me, '2026-11-16 23:59 UTC');

        $inTheDay = self::getContainer()->get(AgendaRetriever::class)->plannedIn($this->me, new CalendarPeriod(new \DateTimeImmutable('2026-11-17 00:00 UTC'), new \DateTimeImmutable('2026-11-18 00:00 UTC')));

        $this->assertSame([self::idOf($atMidnight), self::idOf($atNoon)], self::idsOf($inTheDay));
    }

    public function testTheFirstExchangesIPlannedFromAMomentOnTheSoonestFirst(): void
    {
        $this->plannedBy($this->me, '2026-11-17 23:59 UTC');
        $third = $this->plannedBy($this->me, '2026-11-20 09:00 UTC');
        $first = $this->plannedBy($this->me, '2026-11-18 00:00 UTC');
        $second = $this->plannedBy($this->me, '2026-11-19 09:00 UTC');
        $this->plannedBy($this->me, '2026-11-21 09:00 UTC');

        $next = self::getContainer()->get(AgendaRetriever::class)->firstPlannedFrom($this->me, new \DateTimeImmutable('2026-11-18 00:00 UTC'), 3);

        $this->assertSame([self::idOf($first), self::idOf($second), self::idOf($third)], self::idsOf($next));
    }

    public function testTheExchangesIPlannedStartingBeforeAMomentTheOldestFirst(): void
    {
        $this->plannedBy($this->me, '2026-11-17 14:00 UTC');
        $recent = $this->plannedBy($this->me, '2026-11-17 13:59 UTC');
        $old = $this->plannedBy($this->me, '2026-11-14 09:00 UTC');

        $started = self::getContainer()->get(AgendaRetriever::class)->plannedBefore($this->me, new \DateTimeImmutable('2026-11-17 14:00 UTC'));

        $this->assertSame([self::idOf($old), self::idOf($recent)], self::idsOf($started));
    }

    public function testTheExchangesPlannedByAColleagueAreNotInMyAgenda(): void
    {
        $this->plannedBy(new Prospector(new MemberId(self::newIdentity()), $this->me->accountId, managesPersonalData: true), '2026-11-17 14:00 UTC');

        $this->assertSame([], $this->idsInMyAgendaBetween('2026-11-17 08:00 UTC', '2026-11-17 16:00 UTC'));
    }

    public function testANotedExchangeIsInNoAgenda(): void
    {
        $noted = Exchange::noted(new Gesture($this->me->accountId, $this->me->id, new \DateTimeImmutable(self::NOW)), new ExchangeId(self::newIdentity()), new ContactCorrespondent(new PersonId(self::newIdentity()), [new OrganizationId(self::newIdentity())]), Noting::of(new ExchangeFacts(Channel::Call, Direction::Outbound, new \DateTimeImmutable('2026-11-13 09:00 UTC')), ExchangeStatus::Done));
        $this->exchanges()->save($noted);

        $this->assertSame([], $this->idsInMyAgendaBetween('2026-11-13 08:00 UTC', '2026-11-13 16:00 UTC'));
    }

    public function testARemovedExchangeLeavesTheAgenda(): void
    {
        $planned = $this->plannedBy($this->me, '2026-11-17 14:00 UTC');

        $this->exchanges()->remove($planned);

        $this->assertSame([], $this->idsInMyAgendaBetween('2026-11-17 08:00 UTC', '2026-11-17 16:00 UTC'));
    }

    private function plannedBy(Prospector $holder, string $at): Exchange
    {
        $facts = ExchangeFacts::planned(Channel::Meeting, new \DateTimeImmutable($at), Duration::inMinutes(30), null);
        $exchange = Exchange::planned(new Gesture($holder->accountId, $holder->id, new \DateTimeImmutable(self::NOW)), new ExchangeId(self::newIdentity()), new ContactCorrespondent(new PersonId(self::newIdentity()), [new OrganizationId(self::newIdentity())]), $facts);
        $this->exchanges()->save($exchange);

        return $exchange;
    }

    /**
     * @return list<string>
     */
    private function idsInMyAgendaBetween(string $from, string $until): array
    {
        return self::idsOf(self::getContainer()->get(AgendaRetriever::class)->plannedBy($this->me, new \DateTimeImmutable($from), new \DateTimeImmutable($until)));
    }

    /**
     * @param list<ExchangeInTimeline> $exchanges
     *
     * @return list<string>
     */
    private static function idsOf(array $exchanges): array
    {
        return array_map(static fn (ExchangeInTimeline $exchange): string => $exchange->id, $exchanges);
    }

    private static function idOf(Exchange $exchange): string
    {
        return $exchange->id()->value;
    }

    private function exchanges(): ExchangeRepository
    {
        return self::getContainer()->get(ExchangeRepository::class);
    }
}
