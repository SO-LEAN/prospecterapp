<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Booking\Domain\Event\BookingClosed;
use Prospecter\Core\Booking\Domain\Event\BookingLinkChanged;
use Prospecter\Core\Booking\Domain\Event\BookingOpened;
use Prospecter\Core\Booking\Domain\Event\ChannelsOffered;
use Prospecter\Core\Booking\Domain\Event\InactivePeriodAdded;
use Prospecter\Core\Booking\Domain\Event\InactivePeriodRemoved;
use Prospecter\Core\Booking\Domain\Event\WindowsSet;
use Prospecter\Core\Booking\Domain\Exception\BookingPageWithoutLink;
use Prospecter\Core\Booking\Domain\Model\BookingCode;
use Prospecter\Core\Booking\Domain\Model\BookingPage;
use Prospecter\Core\Booking\Domain\Model\BookingPageIntent;
use Prospecter\Core\Booking\Domain\Model\Channel;
use Prospecter\Core\Booking\Domain\Model\HostTimeZone;
use Prospecter\Core\Booking\Domain\Model\InactivePeriod;
use Prospecter\Core\Booking\Domain\Model\InactivePeriodId;
use Prospecter\Core\Booking\Domain\Model\OfferedChannels;
use Prospecter\Core\Booking\Domain\Model\WeeklyWindows;
use Prospecter\Core\Booking\Domain\Policy\BookingPagePossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A booking page refuses each intention exactly when its state does not allow it. Otherwise, the intention records its
 * event.
 */
final class BookingPageIntentsTest extends TestCase
{
    private const string ACCOUNT = '0190a1c0-0000-7000-8000-000000000001';
    private const string HOST = '0190a1c0-0000-7000-8000-000000000002';
    private const string PERIOD = '0190a1c0-0000-7000-8000-000000000003';
    private const string CODE = 'AbCdEfGhIjKlMnOpQrStUv';
    private const string OTHER_CODE = '0123456789abcdefghijKL';
    private const string NOW = '2026-11-18 10:00';

    public function testAPageNeverOpenedOpensOnly(): void
    {
        $this->assertSame([BookingPageIntent::OpenBooking], BookingPagePossibilities::given(open: false, hasALink: false));
    }

    public function testAnOpenPageClosesAndChangesItsLink(): void
    {
        $this->assertSame([BookingPageIntent::CloseBooking, BookingPageIntent::ChangeBookingLink], BookingPagePossibilities::given(open: true, hasALink: true));
    }

    public function testAClosedPageWithALinkOpensAgainAndChangesItsLink(): void
    {
        $this->assertSame([BookingPageIntent::OpenBooking, BookingPageIntent::ChangeBookingLink], BookingPagePossibilities::given(open: false, hasALink: true));
    }

    public function testAnOpenPageIsNotOpenedAgain(): void
    {
        $page = $this->open();

        $this->expectException(NotPossibleInItsState::class);
        $this->expectExceptionMessageIsOrContains('An open booking page cannot open again');
        $page->open(new BookingCode(self::OTHER_CODE), true, $this->now());
    }

    public function testAClosedPageIsNotClosedAgain(): void
    {
        $this->expectException(NotPossibleInItsState::class);
        $this->expectExceptionMessageIsOrContains('A closed booking page cannot close again');
        $this->aPageNeverSet()->close($this->now());
    }

    public function testAPageWithoutLinkDoesNotChangeIt(): void
    {
        $this->expectExceptionObject(BookingPageWithoutLink::doesNotChangeIt());
        $this->aPageNeverSet()->changeLink(new BookingCode(self::CODE), $this->now());
    }

    public function testOpenedAgainAPageKeepsItsLink(): void
    {
        $page = $this->open();
        $page->close($this->now());
        $page->open(new BookingCode(self::OTHER_CODE), true, $this->now());

        $this->assertSame(self::CODE, $page->code()?->value);
    }

    /**
     * @param \Closure(BookingPage): void $intention
     */
    #[DataProvider('intentions')]
    public function testAnIntentionRecordsItsEvent(\Closure $intention, DomainEvent $event): void
    {
        $page = $this->open();
        $page->releaseEvents();

        $intention($page);

        $this->assertEquals([$event], $page->releaseEvents());
    }

    /**
     * @return iterable<string, array{\Closure(BookingPage): void, DomainEvent}>
     */
    public static function intentions(): iterable
    {
        $account = new AccountId(self::ACCOUNT);
        $host = new MemberId(self::HOST);
        $now = new \DateTimeImmutable(self::NOW);
        $period = new InactivePeriodId(self::PERIOD);

        yield 'set the windows' => [static fn (BookingPage $page) => $page->setWindows(new WeeklyWindows(), new HostTimeZone('Europe/Paris'), $now), new WindowsSet($account, $host, $now)];
        yield 'add an inactive period' => [static fn (BookingPage $page) => $page->addInactivePeriod(new InactivePeriod($period, '2026-12-21', '2027-01-04'), $now), new InactivePeriodAdded($account, $host, $period, $now)];
        yield 'offer channels' => [static fn (BookingPage $page) => $page->offer(new OfferedChannels(Channel::Call), $now), new ChannelsOffered($account, $host, $now)];
        yield 'close' => [static fn (BookingPage $page) => $page->close($now), new BookingClosed($account, $host, $now)];
        yield 'change the link' => [static fn (BookingPage $page) => $page->changeLink(new BookingCode(self::OTHER_CODE), $now), new BookingLinkChanged($account, $host, $now)];
    }

    public function testOpeningRecordsIt(): void
    {
        $this->assertEquals([new ChannelsOffered(new AccountId(self::ACCOUNT), new MemberId(self::HOST), $this->now()), new BookingOpened(new AccountId(self::ACCOUNT), new MemberId(self::HOST), $this->now())], $this->open()->releaseEvents());
    }

    public function testRemovingAPeriodRecordsIt(): void
    {
        $page = $this->aPageNeverSet();
        $page->addInactivePeriod(new InactivePeriod(new InactivePeriodId(self::PERIOD), '2026-12-21', '2027-01-04'), $this->now());
        $page->releaseEvents();
        $page->removeInactivePeriod(new InactivePeriodId(self::PERIOD), $this->now());

        $this->assertEquals([new InactivePeriodRemoved(new AccountId(self::ACCOUNT), new MemberId(self::HOST), new InactivePeriodId(self::PERIOD), $this->now())], $page->releaseEvents());
    }

    private function open(): BookingPage
    {
        $page = $this->aPageNeverSet();
        $page->offer(new OfferedChannels(Channel::Call, Channel::VideoCall), $this->now());
        $page->open(new BookingCode(self::CODE), true, $this->now());

        return $page;
    }

    private function aPageNeverSet(): BookingPage
    {
        return BookingPage::setUpFor(new MemberId(self::HOST), new AccountId(self::ACCOUNT), $this->now());
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::NOW);
    }
}
