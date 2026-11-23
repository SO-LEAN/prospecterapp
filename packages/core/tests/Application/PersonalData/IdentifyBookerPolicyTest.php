<?php

declare(strict_types=1);

namespace Tests\Core\Application\PersonalData;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Booking\Contract\Event\SlotBooked;
use Prospecter\Core\PersonalData\Application\IdentifyBooker\IdentifyBooker;
use Prospecter\Core\PersonalData\Application\Policy\IdentifyBookerPolicy;

class IdentifyBookerPolicyTest extends TestCase
{
    private const string APPOINTMENT = '01960000-0000-7000-8000-000000000001';
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000002';
    private const string HOST = '01960000-0000-7000-8000-000000000003';
    private const string BOOKER = '01960000-0000-7000-8000-000000000004';
    private const string AT = '2026-11-24 09:00 UTC';

    public function testWhoBookedAHostsSlotIsIdentified(): void
    {
        $booked = new SlotBooked(self::APPOINTMENT, self::ACCOUNT, self::HOST, self::BOOKER, new \DateTimeImmutable(self::AT));

        $this->assertEquals(new IdentifyBooker(
            appointmentId: self::APPOINTMENT,
            accountId: self::ACCOUNT,
            hostId: self::HOST,
            bookerId: self::BOOKER,
            executedAt: new \DateTimeImmutable(self::AT),
        ), new IdentifyBookerPolicy()($booked));
    }
}
