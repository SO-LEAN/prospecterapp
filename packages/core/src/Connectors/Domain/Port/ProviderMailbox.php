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
 * Port: a member's mailbox at the provider (ADR 0154). Connectors reaches it with the member's token, through their
 * account's client, and reads its messages. While the mailbox is watched, it notifies Prospecter of its changes
 * through the account's topic. The API implements this port. A transient failure is thrown as it is, so that it is
 * tried again.
 */
interface ProviderMailbox
{
    /**
     * Watches the mailbox, or watches it again. The mailbox then publishes its changes to the topic.
     *
     * @throws AccessRevoked
     */
    public function watch(ClientCredentials $client, RefreshToken $refreshToken, string $topic): MailboxWatch;

    /**
     * Never fails: a watch that the provider could not stop expires within its week.
     */
    public function stop(ClientCredentials $client, RefreshToken $refreshToken): void;

    /**
     * The messages added to the mailbox after this point in its history, the oldest first, up to `$atMost`. When the
     * reading stops before the end, the point reached is the last listed message's point.
     *
     * @throws AccessRevoked|HistoryExpired
     */
    public function messagesAddedSince(ClientCredentials $client, RefreshToken $refreshToken, string $historyId, int $atMost): MailboxChanges;

    /**
     * The messages received or sent after this moment, except the spam, the trash and the drafts. It takes the newest,
     * up to `$atMost`, and leaves out the older ones. The point reached is where the history stood before the listing.
     *
     * @throws AccessRevoked
     */
    public function messagesExchangedAfter(ClientCredentials $client, RefreshToken $refreshToken, \DateTimeImmutable $moment, int $atMost): MailboxChanges;

    /**
     * The messages exchanged with this address after this moment, from it or to it, except the spam, the trash and the
     * drafts. It takes the newest, up to `$atMost`.
     *
     * @return list<string> their ids at the provider
     *
     * @throws AccessRevoked
     */
    public function messagesExchangedWith(ClientCredentials $client, RefreshToken $refreshToken, Email $address, \DateTimeImmutable $moment, int $atMost): array;

    /**
     * A message in the mailbox. None once the message was deleted.
     *
     * @throws AccessRevoked
     */
    public function message(ClientCredentials $client, RefreshToken $refreshToken, string $messageId): ?MailboxMail;

    /**
     * Reads a message's attachment once, and sends it to the private storage as `$copies` uploads (ADR 0182). Each
     * exchange that keeps the attachment gets one upload.
     *
     * @return list<string> the uploads
     *
     * @throws AccessRevoked
     */
    public function depositAttachment(ClientCredentials $client, RefreshToken $refreshToken, string $messageId, MailboxAttachment $attachment, int $copies): array;
}
