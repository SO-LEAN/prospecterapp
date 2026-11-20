<?php

namespace Tests\App\Booking;

use Prospecter\Core\Booking\Domain\Exception\SlotNotFree;
use Prospecter\Core\Booking\Domain\Model\Appointment;
use Prospecter\Core\Booking\Domain\Model\AppointmentId;
use Prospecter\Core\Booking\Domain\Model\Booker;
use Prospecter\Core\Booking\Domain\Model\Channel;
use Prospecter\Core\Booking\Domain\Repository\AppointmentRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The appointments booked by the public link of a host in the table of Booking (ADR 0164): each holds its slot of its
 * host under a condition, so that two bookings of one slot at the same moment never both pass.
 */
class DynamoAppointmentRepositoryTest extends ApiTestCase
{
    private const string SLOT = '2026-10-16T09:00:00+02:00';
    private const string LATER = '2026-10-19T14:00:00+02:00';

    public function testAnAppointmentIsReadBackAsItWasSaved(): void
    {
        $appointment = $this->appointmentOf(new MemberId(self::newIdentity()), self::SLOT, 'Mission data en mars');

        $this->appointments()->save($appointment);
        $read = $this->appointments()->find($appointment->id());

        $this->assertNotNull($read);
        $this->assertSame(
            [$appointment->host()->value, $appointment->bookerId()->value, self::SLOT, 'Europe/Paris', 'videoCall', 'Ada', 'Lovelace', '+33612345678', 'ada@acme.fr', 'Mission data en mars'],
            [$read->host()->value, $read->bookerId()->value, $read->slot()->format(\DATE_ATOM), $read->slot()->getTimezone()->getName(), $read->channel()->value, $read->booker()->firstName, $read->booker()->lastName, $read->booker()->phone->value, $read->booker()->email->value, $read->subject()],
        );
    }

    public function testAnAppointmentNeverSavedIsNotFound(): void
    {
        $this->assertNull($this->appointments()->find(new AppointmentId(self::newIdentity())));
    }

    public function testTheAppointmentsOfAHostToComeAreTheOnesNotEndedTheSoonestFirst(): void
    {
        $host = new MemberId(self::newIdentity());
        $this->appointments()->save($later = $this->appointmentOf($host, self::LATER));
        $this->appointments()->save($sooner = $this->appointmentOf($host, self::SLOT));
        $this->appointments()->save($this->appointmentOf(new MemberId(self::newIdentity()), self::SLOT));

        $toCome = array_map(static fn (Appointment $appointment): string => $appointment->id()->value, $this->appointments()->toComeOf($host, new \DateTimeImmutable('2026-10-16T09:14:00+02:00')));
        $once = array_map(static fn (Appointment $appointment): string => $appointment->id()->value, $this->appointments()->toComeOf($host, new \DateTimeImmutable('2026-10-16T09:15:00+02:00')));

        $this->assertSame([[$sooner->id()->value, $later->id()->value], [$later->id()->value]], [$toCome, $once]);
    }

    public function testASlotTakenMeanwhileRefusesTheSecondAppointment(): void
    {
        $host = new MemberId(self::newIdentity());
        $this->appointments()->save($this->appointmentOf($host, self::SLOT));

        $this->expectException(SlotNotFree::class);

        $this->appointments()->save($this->appointmentOf($host, self::SLOT));
    }

    private function appointmentOf(MemberId $host, string $slot, ?string $subject = null): Appointment
    {
        return Appointment::booked(
            new AppointmentId(self::newIdentity()),
            new AccountId(self::newIdentity()),
            $host,
            new PersonId(self::newIdentity()),
            new \DateTimeImmutable($slot)->setTimezone(new \DateTimeZone('Europe/Paris')),
            Channel::VideoCall,
            Booker::named('Ada', 'Lovelace', '06 12 34 56 78', 'ada@acme.fr'),
            $subject,
            new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS),
        );
    }

    private function appointments(): AppointmentRepository
    {
        return self::getContainer()->get(AppointmentRepository::class);
    }
}
