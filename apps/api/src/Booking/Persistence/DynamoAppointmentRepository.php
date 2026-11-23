<?php

declare(strict_types=1);

namespace App\Booking\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Booking\Domain\Exception\SlotNotFree;
use Prospecter\Core\Booking\Domain\Model\Appointment;
use Prospecter\Core\Booking\Domain\Model\AppointmentId;
use Prospecter\Core\Booking\Domain\Repository\AppointmentRepository;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Symfony\Component\Clock\ClockInterface;

/**
 * The appointments in the Booking table (ADR 0164). An appointment is kept by its id. The slot it holds is kept in the
 * partition of its host's slots, and is put only where no slot is yet. So two bookings of the same slot never both
 * pass. The table forgets both items, with the booker, some days after the last slot that a link offers. They also go
 * together when the exchange that the appointment became is erased (ADR 0171).
 */
final readonly class DynamoAppointmentRepository implements AppointmentRepository
{
    private const string NO_APPOINTMENT_THERE_YET = 'attribute_not_exists(PK)';
    /**
     * Longer than the 15 days in which a link offers slots.
     */
    private const string KEPT_FOR = '+16 days';

    public function __construct(private Table $bookingTable, private ClockInterface $clock)
    {
    }

    public function find(AppointmentId $id): ?Appointment
    {
        $document = $this->bookingTable->item(BookingKeys::appointment($id->value), BookingKeys::APPOINTMENT)['appointment'] ?? null;

        return null === $document ? null : AppointmentDocument::appointment($document);
    }

    public function toComeOf(MemberId $host, \DateTimeImmutable $at): array
    {
        $startedSince = $at->modify(sprintf('-%d minutes', Appointment::MINUTES));
        $items = $this->bookingTable->queryAfter(BookingKeys::slotsOf($host->value), null, BookingKeys::slot(Documents::moment($startedSince)));

        return array_values(array_filter(
            array_map(static fn (array $item): Appointment => AppointmentDocument::appointment($item['appointment']), $items),
            static fn (Appointment $appointment): bool => $appointment->end() > $at,
        ));
    }

    public function save(Appointment $appointment): void
    {
        $document = AppointmentDocument::of($appointment);
        $expiresAt = $this->clock->now()->modify(self::KEPT_FOR)->getTimestamp();

        $this->bookingTable->transact([
            $this->bookingTable->putting(['PK' => BookingKeys::appointment($appointment->id()->value), 'SK' => BookingKeys::APPOINTMENT, 'appointment' => $document, 'expiresAt' => $expiresAt]),
            $this->bookingTable->putting(
                ['PK' => BookingKeys::slotsOf($appointment->host()->value), 'SK' => BookingKeys::slot(Documents::moment($appointment->slot())), 'appointment' => $document, 'expiresAt' => $expiresAt],
                self::NO_APPOINTMENT_THERE_YET,
                refusal: static fn (): SlotNotFree => SlotNotFree::refused(),
            ),
        ]);
    }

    public function remove(Appointment $appointment): void
    {
        $this->bookingTable->transact([
            $this->bookingTable->deleting(BookingKeys::appointment($appointment->id()->value), BookingKeys::APPOINTMENT),
            $this->bookingTable->deleting(BookingKeys::slotsOf($appointment->host()->value), BookingKeys::slot(Documents::moment($appointment->slot()))),
        ]);
    }
}
