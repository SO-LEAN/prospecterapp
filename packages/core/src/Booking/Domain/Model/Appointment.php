<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\Booking\Domain\Event\SlotBooked;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: a slot of a host a booker booked by their public link (ADR 0164). It holds the slot until it ends,
 * the booker's only appointment to come with the host, and who they are until the contexts that plan it read them.
 */
final class Appointment implements ReleasesEvents
{
    use RecordsEvents;

    public const int MINUTES = 15;

    private function __construct(
        private readonly AppointmentId $id,
        private readonly AccountId $accountId,
        private readonly MemberId $host,
        private readonly PersonId $bookerId,
        private readonly \DateTimeImmutable $slot,
        private readonly Channel $channel,
        private readonly Booker $booker,
        private readonly ?string $subject,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * By the booking page of the host, which checks the slot may be booked; the slot on the clock of the host. The
     * booker takes the identity given should nobody of the account be them.
     */
    public static function booked(AppointmentId $id, AccountId $accountId, MemberId $host, PersonId $bookerId, \DateTimeImmutable $slot, Channel $channel, Booker $booker, ?string $subject, \DateTimeImmutable $at): self
    {
        $appointment = new self($id, $accountId, $host, $bookerId, $slot, $channel, $booker, $subject, $at);
        $appointment->recordThat(new SlotBooked($accountId, $host, $id, $bookerId, $at));

        return $appointment;
    }

    public function id(): AppointmentId
    {
        return $this->id;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function host(): MemberId
    {
        return $this->host;
    }

    public function bookerId(): PersonId
    {
        return $this->bookerId;
    }

    public function slot(): \DateTimeImmutable
    {
        return $this->slot;
    }

    public function end(): \DateTimeImmutable
    {
        return $this->slot->modify(sprintf('+%d minutes', self::MINUTES));
    }

    public function channel(): Channel
    {
        return $this->channel;
    }

    public function booker(): Booker
    {
        return $this->booker;
    }

    public function subject(): ?string
    {
        return $this->subject;
    }

    public function isBookedBy(Email $email): bool
    {
        return $this->booker->email->equals($email);
    }

    public function busyPeriod(): BusyPeriod
    {
        return new BusyPeriod($this->slot, self::MINUTES);
    }
}
