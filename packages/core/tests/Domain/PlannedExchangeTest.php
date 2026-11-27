<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Booking\Domain\Model\PlannedExchange;

/**
 * An exchange the host still has planned keeps them busy from its start.
 */
final class PlannedExchangeTest extends TestCase
{
    private const string START = '2026-11-24 09:00 Europe/Paris';

    public function testAnExchangeWithoutADurationKeepsTheHostBusyFor15Minutes(): void
    {
        $busy = new PlannedExchange(new \DateTimeImmutable(self::START), null)->busyPeriod();

        $this->assertEquals([new \DateTimeImmutable(self::START), 15], [$busy->start, $busy->minutes]);
    }

    public function testAnExchangeWithADurationKeepsTheHostBusyForItsMinutes(): void
    {
        $busy = new PlannedExchange(new \DateTimeImmutable(self::START), 45)->busyPeriod();

        $this->assertEquals([new \DateTimeImmutable(self::START), 45], [$busy->start, $busy->minutes]);
    }
}
