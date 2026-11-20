<?php

namespace Tests\App\Double;

use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\MailboxChanges;
use Prospecter\Core\Connectors\Domain\Model\MailboxWatch;
use Prospecter\Core\Connectors\Domain\Model\ReceivedMail;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;

/**
 * A mailbox of Gmail without a message, its history standing where a test says, watched a week from the moment of
 * the tests (ADR 0154): for the tests of the persistence of an import, which read nothing of the mailbox.
 */
final readonly class EmptyMailbox implements ProviderMailbox
{
    private const string WATCHED_UNTIL = '2026-10-22 09:00 UTC';

    public function __construct(private string $historyId = '1000')
    {
    }

    public function watch(ClientCredentials $client, RefreshToken $refreshToken, string $topic): MailboxWatch
    {
        return new MailboxWatch($this->historyId, new \DateTimeImmutable(self::WATCHED_UNTIL));
    }

    public function stop(ClientCredentials $client, RefreshToken $refreshToken): void
    {
    }

    public function messagesAddedSince(ClientCredentials $client, RefreshToken $refreshToken, string $historyId, int $atMost): MailboxChanges
    {
        return new MailboxChanges([], $this->historyId, true);
    }

    public function messagesReceivedAfter(ClientCredentials $client, RefreshToken $refreshToken, \DateTimeImmutable $moment, int $atMost): MailboxChanges
    {
        return new MailboxChanges([], $this->historyId, true);
    }

    public function message(ClientCredentials $client, RefreshToken $refreshToken, string $messageId): ?ReceivedMail
    {
        return null;
    }
}
