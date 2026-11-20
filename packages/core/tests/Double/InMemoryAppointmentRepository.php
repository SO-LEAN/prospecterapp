<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Booking\Domain\Exception\SlotNotFree;
use Prospecter\Core\Booking\Domain\Model\Appointment;
use Prospecter\Core\Booking\Domain\Model\AppointmentId;
use Prospecter\Core\Booking\Domain\Repository\AppointmentRepository;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The DynamoDB repository in the scenarios: an appointment holds its slot of its host, under a condition.
 */
final readonly class InMemoryAppointmentRepository implements AppointmentRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function find(AppointmentId $id): ?Appointment
    {
        return $this->database->appointment($id->value);
    }

    public function toComeOf(MemberId $host, \DateTimeImmutable $at): array
    {
        $toCome = array_filter($this->database->appointments(), static fn (Appointment $appointment): bool => $appointment->host()->equals($host) && $appointment->end() > $at);
        usort($toCome, static fn (Appointment $one, Appointment $other): int => $one->slot() <=> $other->slot());

        return $toCome;
    }

    public function save(Appointment $appointment): void
    {
        if (array_any($this->database->appointments(), static fn (Appointment $other): bool => !$other->id()->equals($appointment->id()) && $other->host()->equals($appointment->host()) && $other->slot() == $appointment->slot())) {
            throw SlotNotFree::refused();
        }
        $this->database->storeAppointment($appointment);
    }

    public function remove(Appointment $appointment): void
    {
        $this->database->forgetAppointment($appointment);
    }
}
