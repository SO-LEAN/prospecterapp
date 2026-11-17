<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Domain\Exception\CampaignWithoutRecipient;
use Prospecter\Core\Campaigns\Domain\Exception\TooManyRecipients;
use Prospecter\Core\Campaigns\Domain\Exception\UnknownRecipient;
use Prospecter\Core\Campaigns\Domain\Model\Recipient;
use Prospecter\Core\Campaigns\Domain\Model\Recipients;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The recipients of a frozen campaign: each once, from 1 to 500 as the members change them, a forgotten contact
 * leaving them whatever is left.
 */
final class RecipientsTest extends TestCase
{
    public function testAContactFoundTwiceIsKeptOnce(): void
    {
        $recipients = Recipients::frozen([self::contact(1), self::contact(2)])->with([self::contact(2), self::contact(3)]);

        $this->assertEquals([Recipient::pending(self::contact(1)), Recipient::pending(self::contact(2)), Recipient::pending(self::contact(3))], $recipients->recipients);
    }

    public function testNothingFoundFreezesNobody(): void
    {
        $this->expectExceptionObject(CampaignWithoutRecipient::left());

        Recipients::frozen([]);
    }

    public function testFiveHundredRecipientsAreKept(): void
    {
        $this->assertCount(Recipients::MAX, Recipients::frozen(self::contacts(Recipients::MAX))->recipients);
    }

    public function testOneMoreIsRefused(): void
    {
        $this->expectExceptionObject(TooManyRecipients::beyond(Recipients::MAX));

        (void) Recipients::frozen(self::contacts(Recipients::MAX))->with([self::contact(Recipients::MAX + 1)]);
    }

    public function testARecipientTakenOffLeavesTheOthers(): void
    {
        $recipients = Recipients::frozen([self::contact(1), self::contact(2)])->without(self::contact(1));

        $this->assertEquals([Recipient::pending(self::contact(2))], $recipients->recipients);
    }

    public function testTheLastRecipientIsNotTakenOff(): void
    {
        $this->expectExceptionObject(CampaignWithoutRecipient::left());

        (void) Recipients::frozen([self::contact(1)])->without(self::contact(1));
    }

    public function testAContactNotKeptIsNotTakenOff(): void
    {
        $this->expectExceptionObject(UnknownRecipient::identifiedBy(self::contact(2)));

        (void) Recipients::frozen([self::contact(1)])->without(self::contact(2));
    }

    public function testAForgottenContactLeavesEvenTheLast(): void
    {
        $recipients = Recipients::frozen([self::contact(1)])->forgetting(self::contact(1));

        $this->assertSame([], $recipients->recipients);
        $this->assertFalse($recipients->includes(self::contact(1)));
    }

    private static function contact(int $number): PersonId
    {
        return new PersonId(sprintf('0190a1c0-0000-7000-8000-%012d', $number));
    }

    /**
     * @return list<PersonId>
     */
    private static function contacts(int $count): array
    {
        return array_map(self::contact(...), range(1, $count));
    }
}
