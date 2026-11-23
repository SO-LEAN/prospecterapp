<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Connectors\Domain\Model\MessageId as MailboxMessageId;
use Prospecter\Core\Prospecting\Domain\Model\MessageId as NotedMessageId;

/**
 * Connectors and Prospecting each have their `MessageId`, and must identify an e-mail alike, or an imported e-mail
 * and the same e-mail an AI logs would not be recognized as one (ADR 0154, 0166).
 */
class MessageIdTest extends TestCase
{
    /**
     * @return iterable<string, array{\Closure(string): (MailboxMessageId|NotedMessageId)}>
     */
    public static function contexts(): iterable
    {
        yield 'Connectors' => [static fn (string $header): MailboxMessageId => new MailboxMessageId($header)];
        yield 'Prospecting' => [static fn (string $header): NotedMessageId => new NotedMessageId($header)];
    }

    /**
     * @param \Closure(string): (MailboxMessageId|NotedMessageId) $messageId
     */
    #[DataProvider('contexts')]
    public function testTheSameEMailIsIdentifiedWithoutItsChevronsNorItsCase(\Closure $messageId): void
    {
        $this->assertSame($messageId('abc@acme.fr')->identity(), $messageId(' <ABC@Acme.fr> ')->identity());
    }

    /**
     * @param \Closure(string): (MailboxMessageId|NotedMessageId) $messageId
     */
    #[DataProvider('contexts')]
    public function testWhatTheEMailSaysStaysOutOfItsIdentity(\Closure $messageId): void
    {
        $this->assertSame(hash('sha256', 'abc@acme.fr'), $messageId('<abc@acme.fr>')->identity());
    }

    /**
     * @param \Closure(string): (MailboxMessageId|NotedMessageId) $messageId
     */
    #[DataProvider('contexts')]
    public function testAnEMailWithoutMessageIdHasNone(\Closure $messageId): void
    {
        $this->assertTrue($messageId(' <> ')->isMissing());
    }
}
