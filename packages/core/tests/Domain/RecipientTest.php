<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Domain\Model\Recipient;
use Prospecter\Core\Campaigns\Domain\Model\RecipientOutcome;
use Prospecter\Core\Campaigns\Domain\Model\RecipientStatus;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A recipient whose contact is erased loses its identity, never what the team did with them.
 */
final class RecipientTest extends TestCase
{
    private const string ADA = '0190a1c0-0000-7000-8000-000000000004';
    private const string EXCHANGE = '0190a1c0-0000-7000-8000-000000000006';

    public function testAProcessedRecipientErasedStaysProcessed(): void
    {
        $recipient = self::ada()->processed(self::EXCHANGE, RecipientOutcome::Done)->erased();

        $this->assertEquals([RecipientStatus::Processed, true], [$recipient->status, $recipient->erased]);
    }

    public function testAMissedCallErasedStaysMissed(): void
    {
        $this->assertTrue(self::ada()->processed(self::EXCHANGE, RecipientOutcome::Missed)->erased()->missed);
    }

    public function testADismissedRecipientErasedStaysDismissed(): void
    {
        $this->assertSame(RecipientStatus::Dismissed, self::ada()->dismissed(null)->erased()->status);
    }

    public function testAPendingRecipientErasedIsNoLongerToHandle(): void
    {
        $this->assertFalse(self::ada()->erased()->isPending());
    }

    public function testAnErasedRecipientLeadsToNoExchangeAnyMore(): void
    {
        $this->assertFalse(self::ada()->processed(self::EXCHANGE, RecipientOutcome::Done)->erased()->isProcessedBy(self::EXCHANGE));
    }

    public function testARecipientWhoseExchangeWentWithItsOrganizationStaysProcessedWithoutIt(): void
    {
        $this->assertEquals(new Recipient(new PersonId(self::ADA), RecipientStatus::Processed, missed: true), self::ada()->processed(self::EXCHANGE, RecipientOutcome::Missed)->withoutItsExchange());
    }

    private static function ada(): Recipient
    {
        return Recipient::pending(new PersonId(self::ADA));
    }
}
