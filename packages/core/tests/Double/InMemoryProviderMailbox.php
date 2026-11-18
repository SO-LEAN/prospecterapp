<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Domain\Exception\AccessRevoked;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\MailboxWatch;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;

/**
 * The mailboxes Gmail keeps, as the scenarios see them (ADR 0154): the mailbox of the account a token was granted
 * for, where its history stands, watched for a week through a topic until stopped; a token the provider refuses
 * reaches nothing.
 */
final class InMemoryProviderMailbox implements ProviderMailbox
{
    private const string WATCH_LASTS = '+7 days';
    private const string FIRST_HISTORY = '1';

    /** @var array<string, string> where the history of each mailbox stands, by its address */
    private array $histories = [];
    /** @var array<string, array{topic: string, expiresAt: \DateTimeImmutable, times: int}> the watches, by mailbox */
    private array $watches = [];

    /**
     * @param \Closure(): \DateTimeImmutable $now the moment of the scenario
     */
    public function __construct(private readonly InMemoryProviderAccess $access, private readonly \Closure $now)
    {
    }

    public function standsAt(string $mailbox, string $historyId): void
    {
        $this->histories[$mailbox] = $historyId;
    }

    public function watch(ClientCredentials $client, RefreshToken $refreshToken, string $topic): MailboxWatch
    {
        if (!$this->access->stillGrants($client, $refreshToken)) {
            throw AccessRevoked::by($client->provider);
        }

        $mailbox = $this->access->accountOf($refreshToken->value);
        $expiresAt = ($this->now)()->modify(self::WATCH_LASTS);
        $this->watches[$mailbox] = ['topic' => $topic, 'expiresAt' => $expiresAt, 'times' => ($this->watches[$mailbox]['times'] ?? 0) + 1];

        return new MailboxWatch($this->histories[$mailbox] ?? self::FIRST_HISTORY, $expiresAt);
    }

    public function stop(ClientCredentials $client, RefreshToken $refreshToken): void
    {
        unset($this->watches[$this->access->accountOf($refreshToken->value)]);
    }

    /**
     * The topic a mailbox publishes its changes to, none while it is not watched.
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
