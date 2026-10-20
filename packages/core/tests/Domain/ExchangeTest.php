<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Channel;
use Prospecter\Core\Prospecting\Domain\Correction;
use Prospecter\Core\Prospecting\Domain\Direction;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeCorrected;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeErased;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoLongerKeptFor;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoted;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeInTheFuture;
use Prospecter\Core\Prospecting\Domain\Exception\MissedOnlyForACall;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\Prospecting\Domain\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\ExchangeId;
use Prospecter\Core\Prospecting\Domain\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

class ExchangeTest extends TestCase
{
    private const string MINE = '0190a1c0-0000-7000-8000-000000000001';
    private const string THEIRS = '0190a1c0-0000-7000-8000-000000000002';
    private const string EXCHANGE = '0190a1c0-0000-7000-8000-000000000003';
    private const string CONTACT = '0190a1c0-0000-7000-8000-000000000004';
    private const string ACME = '0190a1c0-0000-7000-8000-000000000005';
    private const string GROUP = '0190a1c0-0000-7000-8000-000000000006';
    private const string NOW = '2026-10-18 19:00';

    public function testNotingRecordsIt(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Sms, Direction::Inbound, new \DateTimeImmutable(self::NOW)));

        $this->assertEquals([new ExchangeNoted(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    public function testAnExchangeHappeningNowIsNoted(): void
    {
        $this->expectNotToPerformAssertions();

        $this->noted(new ExchangeFacts(Channel::Meeting, Direction::Outbound, new \DateTimeImmutable(self::NOW)));
    }

    public function testAnExchangeAMomentAheadIsInTheFuture(): void
    {
        $this->expectException(ExchangeInTheFuture::class);

        $this->noted(new ExchangeFacts(Channel::Email, Direction::Inbound, new \DateTimeImmutable(self::NOW)->modify('+1 second')));
    }

    #[DataProvider('directions')]
    public function testACallMayBeMissedWhateverItsDirection(Direction $direction): void
    {
        $this->assertTrue(new ExchangeFacts(Channel::Call, $direction, new \DateTimeImmutable(self::NOW), missed: true)->missed);
    }

    /**
     * @return iterable<string, array{Direction}>
     */
    public static function directions(): iterable
    {
        foreach (Direction::cases() as $direction) {
            yield $direction->name => [$direction];
        }
    }

    #[DataProvider('channelsButTheCall')]
    public function testOnlyACallIsMissed(Channel $channel): void
    {
        $this->expectException(MissedOnlyForACall::class);

        new ExchangeFacts($channel, Direction::Outbound, new \DateTimeImmutable(self::NOW), missed: true);
    }

    /**
     * @return iterable<string, array{Channel}>
     */
    public static function channelsButTheCall(): iterable
    {
        foreach (Channel::cases() as $channel) {
            if (Channel::Call !== $channel) {
                yield $channel->name => [$channel];
            }
        }
    }

    public function testABlankTextIsNoText(): void
    {
        $this->assertNull(new ExchangeFacts(Channel::Other, Direction::Inbound, new \DateTimeImmutable(self::NOW), " \n ")->text);
    }

    public function testNoLongerKeptForAnOrganizationIsRecorded(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable(self::NOW)));
        $exchange->releaseEvents();

        $exchange->stopKeepingFor(new OrganizationId(self::ACME), by: $this->me(), at: new \DateTimeImmutable(self::NOW));

        $this->assertEquals([new OrganizationId(self::GROUP)], $exchange->keptFor());
        $this->assertTrue($exchange->isKeptForAnOrganization());
        $this->assertEquals([new ExchangeNoLongerKeptFor(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new OrganizationId(self::ACME), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    public function testKeptForNoOrganizationOnceTheLastIsGone(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable(self::NOW)));

        $exchange->stopKeepingFor(new OrganizationId(self::ACME), by: $this->me(), at: new \DateTimeImmutable(self::NOW));
        $exchange->stopKeepingFor(new OrganizationId(self::GROUP), by: $this->me(), at: new \DateTimeImmutable(self::NOW));

        $this->assertFalse($exchange->isKeptForAnOrganization());
    }

    public function testAnotherAccountDoesNotReachIt(): void
    {
        $exchange = $this->noted(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable(self::NOW)));

        $this->expectException(UnknownExchange::class);

        $exchange->stopKeepingFor(new OrganizationId(self::ACME), by: $this->otherAccount(), at: new \DateTimeImmutable(self::NOW));
    }

    public function testAManagerOfPersonalDataMayCorrectOrEraseIt(): void
    {
        $this->assertSame(ExchangeIntent::cases(), $this->anExchange()->possibleIntentsOf($this->me()));
    }

    public function testNothingIsOfferedWithoutTheRightToManagePersonalData(): void
    {
        $this->assertSame([], $this->anExchange()->possibleIntentsOf($this->colleagueWithoutTheRight()));
    }

    public function testNothingIsOfferedToAnotherAccount(): void
    {
        $this->assertSame([], $this->anExchange()->possibleIntentsOf($this->otherAccount()));
    }

    /**
     * Every intention is refused exactly when the exchange does not offer it.
     */
    #[DataProvider('intentionsAndCallers')]
    public function testAnIntentionIsRefusedExactlyWhenNotOffered(ExchangeIntent $intent, string $caller): void
    {
        $exchange = $this->anExchange();
        $prospector = $this->{$caller}();
        $offered = in_array($intent, $exchange->possibleIntentsOf($prospector), true);

        try {
            $this->carryOut($intent, $exchange, $prospector);
            $refused = false;
        } catch (UnknownExchange|PersonalDataManagersOnly) {
            $refused = true;
        }

        $this->assertSame(!$offered, $refused);
    }

    /**
     * @return iterable<string, array{ExchangeIntent, string}>
     */
    public static function intentionsAndCallers(): iterable
    {
        foreach (ExchangeIntent::cases() as $intent) {
            foreach (['me', 'colleagueWithoutTheRight', 'otherAccount'] as $caller) {
                yield $intent->name.' by '.$caller => [$intent, $caller];
            }
        }
    }

    private function carryOut(ExchangeIntent $intent, Exchange $exchange, Prospector $by): void
    {
        $now = new \DateTimeImmutable(self::NOW);

        match ($intent) {
            ExchangeIntent::CorrectExchange => $exchange->correct(new ExchangeFacts(Channel::Call, Direction::Inbound, $now), by: $by, at: $now),
            ExchangeIntent::EraseExchange => $exchange->erase(by: $by, at: $now),
        };
    }

    public function testCorrectingRecordsItAndWhoCorrected(): void
    {
        $exchange = $this->anExchange();
        $exchange->releaseEvents();
        $later = new \DateTimeImmutable(self::NOW)->modify('+1 day');
        $corrected = new ExchangeFacts(Channel::Email, Direction::Outbound, new \DateTimeImmutable(self::NOW), 'Objet : mission');

        $exchange->correct($corrected, by: $this->me(), at: $later);

        $this->assertSame($corrected, $exchange->facts());
        $this->assertEquals(new Correction($this->me()->id, $later), $exchange->lastCorrection());
        $this->assertEquals([new ExchangeCorrected(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), $later)], $exchange->releaseEvents());
    }

    public function testANotedExchangeIsNotCorrected(): void
    {
        $this->assertNull($this->anExchange()->lastCorrection());
    }

    public function testACorrectionIsNeverInTheFuture(): void
    {
        $this->expectException(ExchangeInTheFuture::class);

        $this->anExchange()->correct(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable(self::NOW)->modify('+1 second')), by: $this->me(), at: new \DateTimeImmutable(self::NOW));
    }

    public function testCorrectingNeedsTheRightToManagePersonalData(): void
    {
        $this->expectException(PersonalDataManagersOnly::class);

        $this->anExchange()->correct(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable(self::NOW)), by: $this->colleagueWithoutTheRight(), at: new \DateTimeImmutable(self::NOW));
    }

    public function testAnotherAccountDoesNotCorrectIt(): void
    {
        $this->expectException(UnknownExchange::class);

        $this->anExchange()->correct(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable(self::NOW)), by: $this->otherAccount(), at: new \DateTimeImmutable(self::NOW));
    }

    public function testErasingRecordsIt(): void
    {
        $exchange = $this->anExchange();
        $exchange->releaseEvents();

        $exchange->erase(by: $this->me(), at: new \DateTimeImmutable(self::NOW));

        $this->assertEquals([new ExchangeErased(new AccountId(self::MINE), $this->me()->id, new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::NOW))], $exchange->releaseEvents());
    }

    public function testErasingNeedsTheRightToManagePersonalData(): void
    {
        $this->expectException(PersonalDataManagersOnly::class);

        $this->anExchange()->erase(by: $this->colleagueWithoutTheRight(), at: new \DateTimeImmutable(self::NOW));
    }

    public function testAnotherAccountDoesNotEraseIt(): void
    {
        $this->expectException(UnknownExchange::class);

        $this->anExchange()->erase(by: $this->otherAccount(), at: new \DateTimeImmutable(self::NOW));
    }

    private function anExchange(): Exchange
    {
        return $this->noted(new ExchangeFacts(Channel::LinkedIn, Direction::Inbound, new \DateTimeImmutable(self::NOW), 'Intéressée'));
    }

    private function noted(ExchangeFacts $facts): Exchange
    {
        return Exchange::notedBy($this->me(), new ExchangeId(self::EXCHANGE), new PersonId(self::CONTACT), [new OrganizationId(self::ACME), new OrganizationId(self::GROUP)], $facts, new \DateTimeImmutable(self::NOW));
    }

    private function me(): Prospector
    {
        return new Prospector(new MemberId(self::MINE), new AccountId(self::MINE), true);
    }

    private function colleagueWithoutTheRight(): Prospector
    {
        return new Prospector(new MemberId(self::THEIRS), new AccountId(self::MINE));
    }

    private function otherAccount(): Prospector
    {
        return new Prospector(new MemberId(self::THEIRS), new AccountId(self::THEIRS), true);
    }
}
