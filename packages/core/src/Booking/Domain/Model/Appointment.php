<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\Booking\Domain\Event\SlotBooked;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Event\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\Event\ReleasesEvents;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Aggregate root: a host's slot that a booker booked through the public link (ADR 0164). It holds the slot until the
 * slot ends, or until the exchange it became is erased (ADR 0171). It is the booker's only upcoming appointment with
 * the host. It keeps who the booker is until the contexts that plan the appointment read it.
 */
final class Appointment implements ReleasesEvents
{
    use RecordsEvents;

    public const int MINUTES = 15;

    private function __construct(
        private readonly AppointmentId $id,
        private readonly AccountId $accountId,
        private readonly MemberId $host,
        private readonly BookerId $bookerId,
        private readonly \DateTimeImmutable $slot,
        private readonly Channel $channel,
        private readonly Booker $booker,
        private readonly ?string $subject,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * The host's booking page calls it after it checks that the slot is bookable. The slot is on the host's clock. The
     * booker takes the given id when nobody in the account matches them.
     */
    public static function booked(AccountId $accountId, MemberId $host, AppointmentRequest $request, \DateTimeImmutable $at): self
    {
        $appointment = new self($request->id, $accountId, $host, $request->bookerId, $request->slot, $request->channel, $request->booker, $request->subject, $at);
        $appointment->recordThat(new SlotBooked($accountId, $host, $request->id, $request->bookerId, $at));

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

    public function bookerId(): BookerId
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
        return $this->booker->hasEmail($email);
    }

    public function busyPeriod(): BusyPeriod
    {
        return new BusyPeriod($this->slot, self::MINUTES);
    }
}
