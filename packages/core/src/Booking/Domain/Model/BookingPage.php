<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\Booking\Domain\Event\BookingClosed;
use Prospecter\Core\Booking\Domain\Event\BookingLinkChanged;
use Prospecter\Core\Booking\Domain\Event\BookingOpened;
use Prospecter\Core\Booking\Domain\Event\ChannelsOffered;
use Prospecter\Core\Booking\Domain\Event\InactivePeriodAdded;
use Prospecter\Core\Booking\Domain\Event\InactivePeriodRemoved;
use Prospecter\Core\Booking\Domain\Event\WindowsSet;
use Prospecter\Core\Booking\Domain\Exception\BookingPageWithoutLink;
use Prospecter\Core\Booking\Domain\Exception\NoChannelOffered;
use Prospecter\Core\Booking\Domain\Exception\NoConnectedCalendar;
use Prospecter\Core\Booking\Domain\Exception\UnknownInactivePeriod;
use Prospecter\Core\Booking\Domain\Policy\BookingPagePossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: the booking page of a member, the host, one each: the windows of their week, the days they are
 * inactive, the channels they offer, whether it is open, and the code of its public link (ADR 0148).
 */
final class BookingPage implements ReleasesEvents
{
    use RecordsEvents;

    private ?HostTimeZone $timeZone = null;
    /** @var array<string, InactivePeriod> by their identity */
    private array $inactivePeriods = [];
    private bool $open = false;
    private ?BookingCode $code = null;
    private \DateTimeImmutable $modifiedAt;

    private function __construct(
        private readonly MemberId $host,
        private readonly AccountId $accountId,
        private WeeklyWindows $windows,
        private OfferedChannels $channels,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->modifiedAt = $createdAt;
    }

    /**
     * Closed, without windows, channels nor link: set up by the first gesture of its host on it.
     */
    public static function setUpFor(MemberId $host, AccountId $accountId, \DateTimeImmutable $at): self
    {
        return new self($host, $accountId, new WeeklyWindows(), new OfferedChannels(), $at);
    }

    public function setWindows(WeeklyWindows $windows, HostTimeZone $timeZone, \DateTimeImmutable $at): void
    {
        $this->windows = $windows;
        $this->timeZone = $timeZone;
        $this->changedAt($at);
        $this->recordThat(new WindowsSet($this->accountId, $this->host, $at));
    }

    public function addInactivePeriod(InactivePeriod $period, \DateTimeImmutable $at): void
    {
        $this->inactivePeriods[$period->id->value] = $period;
        $this->changedAt($at);
        $this->recordThat(new InactivePeriodAdded($this->accountId, $this->host, $period->id, $at));
    }

    public function removeInactivePeriod(InactivePeriodId $period, \DateTimeImmutable $at): void
    {
        if (!isset($this->inactivePeriods[$period->value])) {
            throw UnknownInactivePeriod::identifiedBy($period);
        }
        unset($this->inactivePeriods[$period->value]);
        $this->changedAt($at);
        $this->recordThat(new InactivePeriodRemoved($this->accountId, $this->host, $period, $at));
    }

    public function offer(OfferedChannels $channels, \DateTimeImmutable $at): void
    {
        if ($this->open && !$channels->offerSome()) {
            throw NoChannelOffered::refused();
        }
        $this->channels = $channels;
        $this->changedAt($at);
        $this->recordThat(new ChannelsOffered($this->accountId, $this->host, $at));
    }

    /**
     * The calendar of the host invites the booker: without one connected, nobody books. The code drawn serves only
     * the first opening; opened again, the page keeps its link.
     */
    public function open(BookingCode $drawn, bool $calendarConnected, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(BookingPageIntent::OpenBooking);
        if (!$calendarConnected) {
            throw NoConnectedCalendar::refused();
        }
        if (!$this->channels->offerSome()) {
            throw NoChannelOffered::refused();
        }
        $this->code ??= $drawn;
        $this->open = true;
        $this->changedAt($at);
        $this->recordThat(new BookingOpened($this->accountId, $this->host, $at));
    }

    public function close(\DateTimeImmutable $at): void
    {
        $this->ensurePossible(BookingPageIntent::CloseBooking);
        $this->open = false;
        $this->changedAt($at);
        $this->recordThat(new BookingClosed($this->accountId, $this->host, $at));
    }

    public function changeLink(BookingCode $drawn, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(BookingPageIntent::ChangeBookingLink);
        $this->code = $drawn;
        $this->changedAt($at);
        $this->recordThat(new BookingLinkChanged($this->accountId, $this->host, $at));
    }

    public function host(): MemberId
    {
        return $this->host;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function code(): ?BookingCode
    {
        return $this->code;
    }

    public function isOpen(): bool
    {
        return $this->open;
    }

    /**
     * @param string $day YYYY-MM-DD, on the clock of the host
     */
    public function isUnavailableOn(string $day): bool
    {
        return array_any($this->inactivePeriods, static fn (InactivePeriod $period): bool => $period->covers($day));
    }

    private function changedAt(\DateTimeImmutable $at): void
    {
        $this->modifiedAt = $at;
    }

    private function ensurePossible(BookingPageIntent $intent): void
    {
        if (!in_array($intent, BookingPagePossibilities::given($this->open, null !== $this->code), true)) {
            throw $this->refusalOf($intent);
        }
    }

    private function refusalOf(BookingPageIntent $intent): NotPossibleInItsState
    {
        return match ($intent) {
            BookingPageIntent::ChangeBookingLink => BookingPageWithoutLink::doesNotChangeIt(),
            BookingPageIntent::OpenBooking => NotPossibleInItsState::because('An open booking page does not open again'),
            BookingPageIntent::CloseBooking => NotPossibleInItsState::because('A closed booking page does not close again'),
        };
    }
}
