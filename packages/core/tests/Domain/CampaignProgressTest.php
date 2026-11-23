<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Domain\Rule\CampaignProgress;

/**
 * The recipients processed today are counted on the member's clock.
 */
final class CampaignProgressTest extends TestCase
{
    public function testARecipientProcessedEarlierTodayCounts(): void
    {
        $this->assertSame(1, CampaignProgress::processedOnTheDayOf(new \DateTimeImmutable('2026-11-16 17:00 UTC'), new \DateTimeZone('UTC'), [new \DateTimeImmutable('2026-11-16 09:00 UTC')]));
    }

    public function testARecipientProcessedYesterdayOnTheMembersClockDoesNotCount(): void
    {
        $this->assertSame(0, CampaignProgress::processedOnTheDayOf(new \DateTimeImmutable('2026-11-16 23:30 UTC'), new \DateTimeZone('Europe/Paris'), [new \DateTimeImmutable('2026-11-16 22:30 UTC')]));
    }

    public function testARecipientNotProcessedDoesNotCount(): void
    {
        $this->assertSame(0, CampaignProgress::processedOnTheDayOf(new \DateTimeImmutable('2026-11-16 17:00 UTC'), new \DateTimeZone('UTC'), [null]));
    }
}
