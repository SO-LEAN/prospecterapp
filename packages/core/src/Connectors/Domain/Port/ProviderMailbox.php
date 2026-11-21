<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\Connectors\Domain\Exception\AccessRevoked;
use Prospecter\Core\Connectors\Domain\Exception\HistoryExpired;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\MailboxAttachment;
use Prospecter\Core\Connectors\Domain\Model\MailboxChanges;
use Prospecter\Core\Connectors\Domain\Model\MailboxMail;
use Prospecter\Core\Connectors\Domain\Model\MailboxWatch;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\SharedKernel\Domain\Email;

/**
 * Port: the mailbox of a member at the provider, reached with their token through the client of their account, which
 * notifies Prospecter of its changes through the topic of the account while it is watched, and whose messages are read
 * (ADR 0154), implemented by the API. A failure that may pass is thrown as it is, to be tried again.
 */
interface ProviderMailbox
{
    /**
     * The mailbox watched, or watched again, publishing its changes to the topic.
     *
     * @throws AccessRevoked
     */
    public function watch(ClientCredentials $client, RefreshToken $refreshToken, string $topic): MailboxWatch;

    /**
     * Never fails: a watch the provider could not stop expires within its week.
     */
    public function stop(ClientCredentials $client, RefreshToken $refreshToken): void;

    /**
     * The messages added to the mailbox after this point of its history, the oldest first, at most so many: the point
     * reached is that of the last one listed when the reading stops before the end.
     *
     * @throws AccessRevoked|HistoryExpired
     */
    public function messagesAddedSince(ClientCredentials $client, RefreshToken $refreshToken, string $historyId, int $atMost): MailboxChanges;

    /**
     * The messages received or sent after this moment, but the spam, the trash and the drafts, the newest at most so
     * many, the older ones left out; the point reached is where the history stood before they were listed.
     *
     * @throws AccessRevoked
     */
    public function messagesExchangedAfter(ClientCredentials $client, RefreshToken $refreshToken, \DateTimeImmutable $moment, int $atMost): MailboxChanges;

    /**
     * The messages exchanged with this address after this moment, from it or to it, but the spam, the trash and the
     * drafts, the newest at most so many.
     *
     * @return list<string> their ids at the provider
     *
     * @throws AccessRevoked
     */
    public function messagesExchangedWith(ClientCredentials $client, RefreshToken $refreshToken, Email $address, \DateTimeImmutable $moment, int $atMost): array;

    /**
     * A message of the mailbox, none once it was deleted.
     *
     * @throws AccessRevoked
     */
    public function message(ClientCredentials $client, RefreshToken $refreshToken, string $messageId): ?MailboxMail;

    /**
     * An attachment of a message, read once and sent to the private storage as so many uploads, one for each exchange
     * that will keep it (ADR 0182).
     *
     * @return list<string> the uploads
     *
     * @throws AccessRevoked
     */
    public function depositAttachment(ClientCredentials $client, RefreshToken $refreshToken, string $messageId, MailboxAttachment $attachment, int $copies): array;
}
