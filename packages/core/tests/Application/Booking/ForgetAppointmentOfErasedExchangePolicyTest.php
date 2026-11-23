<?php

declare(strict_types=1);

namespace Tests\Core\Application\Booking;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Booking\Application\Appointment\ForgetAppointmentOfErasedExchange\ForgetAppointmentOfErasedExchange;
use Prospecter\Core\Booking\Application\Policy\ForgetAppointmentOfErasedExchangePolicy;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;

class ForgetAppointmentOfErasedExchangePolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string EXCHANGE = '01960000-0000-7000-8000-000000000002';
    private const string AT = '2026-11-23 09:00 UTC';

    public function testTheAppointmentAnErasedExchangeWasBookedAsIsForgotten(): void
    {
        $erased = new ExchangeFactsChanged(self::ACCOUNT, self::EXCHANGE, 0, false, null, null, new \DateTimeImmutable(self::AT));

        $this->assertEquals(new ForgetAppointmentOfErasedExchange(self::EXCHANGE, new \DateTimeImmutable(self::AT)), new ForgetAppointmentOfErasedExchangePolicy()($erased));
    }

    public function testAnExchangeStillPresentKeepsItsAppointment(): void
    {
        $planned = new ExchangeFactsChanged(self::ACCOUNT, self::EXCHANGE, 2, true, 'planned', null, new \DateTimeImmutable(self::AT));

        $this->assertNull(new ForgetAppointmentOfErasedExchangePolicy()($planned));
    }
}
