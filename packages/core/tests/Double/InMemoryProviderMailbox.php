<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Domain\Exception\AccessRevoked;
use Prospecter\Core\Connectors\Domain\Exception\HistoryExpired;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\MailboxAttachment;
use Prospecter\Core\Connectors\Domain\Model\MailboxChanges;
use Prospecter\Core\Connectors\Domain\Model\MailboxMail;
use Prospecter\Core\Connectors\Domain\Model\MailboxWatch;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;

/**
 * The mailboxes that Gmail keeps, as the scenarios see them (ADR 0154). A token reaches the mailbox of the account it
 * was granted for. The double keeps where each mailbox history stands, and each message received moves it one point
 * further. A mailbox is watched for a week through a topic, until the watch is stopped. Its history is forgotten when
 * a scenario says so, as Gmail forgets it after a week or so. A token that the provider refuses reaches nothing.
 */
final class InMemoryProviderMailbox implements ProviderMailbox
{
    private const string WATCH_LASTS = '+7 days';
    private const int FIRST_HISTORY = 1;
    private const string ANY_FILE = 'application/octet-stream';

    /** @var array<string, int> where each mailbox history stands, by the mailbox's address */
    private array $histories = [];
    /** @var array<string, int> for each mailbox, the history point before which it no longer keeps its history */
    private array $forgottenBefore = [];
    /** @var array<string, list<array{history: int, mail: MailboxMail, copied: list<Email>}>> the messages of each mailbox, the oldest first */
    private array $messages = [];
    /** @var array<string, array{topic: string, expiresAt: \DateTimeImmutable, times: int}> the watches, by mailbox */
    private array $watches = [];
    /** @var array<string, true> the mailboxes whose messages Gmail refuses to read, because the access was revoked in the meantime */
    private array $messagesRefused = [];

    /**
     * @param \Closure(): \DateTimeImmutable $now the moment of the scenario
     */
    public function __construct(private readonly InMemoryProviderAccess $access, private readonly InMemoryPrivateStorage $privateStorage, private readonly \Closure $now)
    {
    }

    public function standsAt(string $mailbox, string $historyId): void
    {
        $this->histories[$mailbox] = (int) $historyId;
    }

    /**
     * A message arrives in the mailbox, and its history moves one point further. The message comes with the addresses
     * that it was copied to. Gmail searches them, but the message as Prospecter reads it does not name them.
     *
     * @param list<Email> $copied
     */
    public function receive(string $mailbox, MailboxMail $mail, array $copied = []): void
    {
        $this->messages[$mailbox][] = ['history' => $this->moveOn($mailbox), 'mail' => $mail, 'copied' => $copied];
    }

    /**
     * The mailbox history tells again about a message that the mailbox has, as when a label is added to the message.
     */
    public function addAgain(string $mailbox, MailboxMail $mail): void
    {
        $this->receive($mailbox, $mail);
    }

    private function moveOn(string $mailbox): int
    {
        return $this->histories[$mailbox] = ($this->histories[$mailbox] ?? self::FIRST_HISTORY) + 1;
    }

    public function historyOf(string $mailbox): string
    {
        return (string) ($this->histories[$mailbox] ?? self::FIRST_HISTORY);
    }

    public function forgetTheHistoryOf(string $mailbox): void
    {
        $this->forgottenBefore[$mailbox] = $this->histories[$mailbox] ?? self::FIRST_HISTORY;
    }

    public function watch(ClientCredentials $client, RefreshToken $refreshToken, string $topic): MailboxWatch
    {
        $mailbox = $this->mailboxOf($client, $refreshToken);
        $expiresAt = ($this->now)()->modify(self::WATCH_LASTS);
        $this->watches[$mailbox] = ['topic' => $topic, 'expiresAt' => $expiresAt, 'times' => ($this->watches[$mailbox]['times'] ?? 0) + 1];

        return new MailboxWatch($this->historyOf($mailbox), $expiresAt);
    }

    public function stop(ClientCredentials $client, RefreshToken $refreshToken): void
    {
        unset($this->watches[$this->access->accountOf($refreshToken->value)]);
    }

    public function messagesAddedSince(ClientCredentials $client, RefreshToken $refreshToken, string $historyId, int $atMost): MailboxChanges
    {
        $mailbox = $this->mailboxOf($client, $refreshToken);
        if ((int) $historyId < ($this->forgottenBefore[$mailbox] ?? 0)) {
            throw HistoryExpired::from($historyId);
        }

        $added = array_values(array_filter($this->messages[$mailbox] ?? [], static fn (array $message): bool => $message['history'] > (int) $historyId));
        $listed = array_slice($added, 0, $atMost);
        $complete = count($listed) === count($added);

        return new MailboxChanges(array_map(static fn (array $message): string => $message['mail']->providerId, $listed), $complete ? $this->historyOf($mailbox) : (string) (array_last($listed) ?? throw new \LogicException('A message was expected to be listed'))['history'], $complete);
    }

    public function messagesExchangedAfter(ClientCredentials $client, RefreshToken $refreshToken, \DateTimeImmutable $moment, int $atMost): MailboxChanges
    {
        $mailbox = $this->mailboxOf($client, $refreshToken);
        $exchanged = array_filter($this->messages[$mailbox] ?? [], static fn (array $message): bool => $message['mail']->exchangedAt > $moment && ($message['mail']->isReceived() || $message['mail']->isSent()));

        return new MailboxChanges(array_map(static fn (array $message): string => $message['mail']->providerId, array_slice($exchanged, -$atMost)), $this->historyOf($mailbox), true);
    }

    /**
     * As Gmail searches `from:` or `to:` an address, copies included. The search leaves out the spam, the trash and the
     * drafts. The newest messages come first, by the moment they were exchanged.
     */
    public function messagesExchangedWith(ClientCredentials $client, RefreshToken $refreshToken, Email $address, \DateTimeImmutable $moment, int $atMost): array
    {
        $mailbox = $this->mailboxOf($client, $refreshToken);
        $exchanged = array_filter($this->messages[$mailbox] ?? [], static fn (array $message): bool => $message['mail']->exchangedAt > $moment
            && ($message['mail']->isReceived() || $message['mail']->isSent())
            && array_any([$message['mail']->sender, ...$message['mail']->recipients, ...$message['copied']], static fn (Email $mentioned): bool => $mentioned->equals($address)));
        usort($exchanged, static fn (array $newer, array $older): int => $older['mail']->exchangedAt <=> $newer['mail']->exchangedAt);

        return array_map(static fn (array $message): string => $message['mail']->providerId, array_slice($exchanged, 0, $atMost));
    }

    /**
     * The access is revoked once the history was read, before its messages are read.
     */
    public function refusesTheMessagesOf(string $mailbox): void
    {
        $this->messagesRefused[$mailbox] = true;
    }

    public function message(ClientCredentials $client, RefreshToken $refreshToken, string $messageId): ?MailboxMail
    {
        $mailbox = $this->mailboxOf($client, $refreshToken);
        if (isset($this->messagesRefused[$mailbox])) {
            throw AccessRevoked::by($client->provider);
        }

        return array_find($this->messages[$mailbox] ?? [], static fn (array $message): bool => $message['mail']->providerId === $messageId)['mail'] ?? null;
    }

    /**
     * Sent as the API sends a file, in an upload's form: a file of the attachment's size.
     */
    public function depositAttachment(ClientCredentials $client, RefreshToken $refreshToken, string $messageId, MailboxAttachment $attachment, int $copies): array
    {
        $this->mailboxOf($client, $refreshToken);

        return array_map(fn (): string => $this->privateStorage->send(str_repeat('x', $attachment->size), self::ANY_FILE), range(1, $copies));
    }

    /**
     * @throws AccessRevoked
     */
    private function mailboxOf(ClientCredentials $client, RefreshToken $refreshToken): string
    {
        if (!$this->access->stillGrants($client, $refreshToken)) {
            throw AccessRevoked::by($client->provider);
        }

        return $this->access->accountOf($refreshToken->value);
    }

    /**
     * The topic that a mailbox publishes its changes to, null while the mailbox is not watched.
     */
    public function topicWatchedBy(string $mailbox): ?string
    {
        return $this->watches[$mailbox]['topic'] ?? null;
    }

    public function timesWatched(string $mailbox): int
    {
        return $this->watches[$mailbox]['times'] ?? 0;
    }
}
