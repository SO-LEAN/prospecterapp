<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Domain\Exception\InvalidDailyCap;
use Prospecter\Core\Campaigns\Domain\Model\DailyCap;

/**
 * A daily cap is a guard of at least one send a day.
 */
final class DailyCapTest extends TestCase
{
    public function testACapOfOneSendIsKept(): void
    {
        $this->assertSame(1, DailyCap::of(1)->sends);
    }

    public function testACapOfNoSendIsRefused(): void
    {
        $this->expectExceptionObject(InvalidDailyCap::belowOne());

        (void) DailyCap::of(0);
    }
}
