<?php

declare(strict_types=1);

namespace Tests\Core\Application\Prospecting;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\PersonalData\Contract\Event\BookerIdentified;
use Prospecter\Core\Prospecting\Application\Exchange\PlanBookedAppointment\PlanBookedAppointment;
use Prospecter\Core\Prospecting\Application\Policy\PlanBookedAppointmentPolicy;

class PlanBookedAppointmentPolicyTest extends TestCase
{
    private const string APPOINTMENT = '01960000-0000-7000-8000-000000000001';
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000002';
    private const string HOST = '01960000-0000-7000-8000-000000000003';
    private const string BOOKER = '01960000-0000-7000-8000-000000000004';
    private const string AT = '2026-11-25 09:00 UTC';

    public function testTheHostPlansTheExchangeTheIdentifiedBookerBooked(): void
    {
        $identified = new BookerIdentified(self::APPOINTMENT, self::ACCOUNT, self::HOST, self::BOOKER, new \DateTimeImmutable(self::AT));

        $this->assertEquals(new PlanBookedAppointment(
            appointmentId: self::APPOINTMENT,
            hostId: self::HOST,
            contactId: self::BOOKER,
            executedAt: new \DateTimeImmutable(self::AT),
        ), new PlanBookedAppointmentPolicy()($identified));
    }
}
