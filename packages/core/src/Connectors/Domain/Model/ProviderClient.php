<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\Connectors\Domain\Event\ClientConfigured;
use Prospecter\Core\Connectors\Domain\Event\ClientRemoved;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: the Prospecter client that an account created at a provider, in its own space there. The account's
 * members connect through it. An account has one client per provider (ADR 0125).
 */
final class ProviderClient implements ReleasesEvents
{
    use RecordsEvents;

    private ?MailboxNotifications $mailboxNotifications = null;
    private \DateTimeImmutable $modifiedAt;

    private function __construct(
        private readonly AccountId $accountId,
        private ClientCredentials $credentials,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->modifiedAt = $createdAt;
    }

    public static function configure(AccountId $accountId, ClientCredentials $credentials, MemberId $by, \DateTimeImmutable $at): self
    {
        $client = new self($accountId, $credentials, $at);
        $client->recordThat(new ClientConfigured($accountId, $by, $at));

        return $client;
    }

    /**
     * Replaces the credentials with another client, or with a new secret for the same client. A token granted to a
     * former client is not valid with another one.
     */
    public function replaceBy(ClientCredentials $credentials, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->credentials = $credentials;
        $this->modifiedAt = $at;
        $this->recordThat(new ClientConfigured($this->accountId, $by, $at));
    }

    /**
     * From now on, the members' mailboxes notify Prospecter through this topic (ADR 0154).
     */
    public function notifyMailboxesThrough(MailboxNotifications $notifications, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->mailboxNotifications = $notifications;
        $this->modifiedAt = $at;
        $this->recordThat(new ClientConfigured($this->accountId, $by, $at));
    }

    /**
     * The members' mailboxes no longer notify Prospecter. The e-mail import is no longer offered.
     */
    public function stopMailboxNotificationsBy(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->mailboxNotifications = null;
        $this->modifiedAt = $at;
        $this->recordThat(new ClientConfigured($this->accountId, $by, $at));
    }

    public function removeBy(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->recordThat(new ClientRemoved($this->accountId, $by, $at));
    }

    public function credentials(): ClientCredentials
    {
        return $this->credentials;
    }

    public function mailboxNotifications(): ?MailboxNotifications
    {
        return $this->mailboxNotifications;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function provider(): Provider
    {
        return $this->credentials->provider;
    }
}
