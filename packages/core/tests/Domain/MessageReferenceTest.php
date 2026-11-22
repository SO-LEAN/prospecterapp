<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Exception\ReferenceNotOfTheChannel;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFingerprint;
use Prospecter\Core\Prospecting\Domain\Model\MessageReference;
use Prospecter\Core\SharedKernel\Domain\MessageId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

class MessageReferenceTest extends TestCase
{
    private const string ADA = '0190a0a0-0000-7000-8000-000000000001';
    private const string AT = '2026-11-18 09:12 UTC';

    public function testAnEMailIsFingerprintedByItsMessageId(): void
    {
        $reference = MessageReference::of(Channel::Email, messageId: '<abc@acme.fr>', linkedInConversation: null);

        $this->assertEquals(ExchangeFingerprint::ofEmail(new MessageId('abc@acme.fr')->identity(), self::ada()), $reference->fingerprintOf(self::facts(Channel::Email), self::ada()));
    }

    public function testALinkedInMessageIsFingerprintedByItsConversation(): void
    {
        $reference = MessageReference::of(Channel::LinkedIn, messageId: null, linkedInConversation: '2-abc');

        $this->assertEquals(ExchangeFingerprint::ofLinkedInMessage('2-abc', Direction::Inbound, new \DateTimeImmutable(self::AT), 'Bonjour'), $reference->fingerprintOf(self::facts(Channel::LinkedIn), self::ada()));
    }

    public function testAConversationIsReadFromTheAddressOfItsThread(): void
    {
        $fromItsAddress = MessageReference::of(Channel::LinkedIn, messageId: null, linkedInConversation: 'https://www.linkedin.com/messaging/thread/2-abc/?lipi=xyz');
        $bare = MessageReference::of(Channel::LinkedIn, messageId: null, linkedInConversation: ' 2-abc ');

        $this->assertEquals($bare->fingerprintOf(self::facts(Channel::LinkedIn), self::ada()), $fromItsAddress->fingerprintOf(self::facts(Channel::LinkedIn), self::ada()));
    }

    public function testOnlyAnEMailHasAMessageId(): void
    {
        $this->expectException(ReferenceNotOfTheChannel::class);

        MessageReference::of(Channel::Call, messageId: '<abc@acme.fr>', linkedInConversation: null);
    }

    public function testOnlyALinkedInMessageHasAConversation(): void
    {
        $this->expectException(ReferenceNotOfTheChannel::class);

        MessageReference::of(Channel::Email, messageId: null, linkedInConversation: '2-abc');
    }

    public function testAnEMailWithoutMessageIdIsFingerprintedAsAnyMessage(): void
    {
        $reference = MessageReference::of(Channel::Email, messageId: ' <> ', linkedInConversation: null);

        $this->assertEquals(ExchangeFingerprint::ofMessage(self::facts(Channel::Email), self::ada()), $reference->fingerprintOf(self::facts(Channel::Email), self::ada()));
    }

    public function testALinkedInMessageWithoutConversationIsFingerprintedAsAnyMessage(): void
    {
        $reference = MessageReference::of(Channel::LinkedIn, messageId: null, linkedInConversation: ' ');

        $this->assertEquals(ExchangeFingerprint::ofMessage(self::facts(Channel::LinkedIn), self::ada()), $reference->fingerprintOf(self::facts(Channel::LinkedIn), self::ada()));
    }

    private static function facts(Channel $channel, string $text = 'Bonjour'): ExchangeFacts
    {
        return new ExchangeFacts($channel, Direction::Inbound, new \DateTimeImmutable(self::AT), $text);
    }

    private static function ada(): PersonId
    {
        return new PersonId(self::ADA);
    }
}
