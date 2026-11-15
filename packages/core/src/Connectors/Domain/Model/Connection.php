<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\Connectors\Domain\Event\Connected;
use Prospecter\Core\Connectors\Domain\Event\ConnectionLost;
use Prospecter\Core\Connectors\Domain\Event\Disconnected;
use Prospecter\Core\Connectors\Domain\Exception\IncompleteGrant;
use Prospecter\Core\Connectors\Domain\Port\ProviderAccess;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: the account a member connected at a provider, the lasting access it gave, and the client of their
 * account it was granted to; one per member and provider, theirs only (ADR 0120, 0125).
 */
final class Connection implements ReleasesEvents
{
    use RecordsEvents;

    private ConnectionState $state = ConnectionState::Connected;
    private \DateTimeImmutable $modifiedAt;

    private function __construct(
        private readonly MemberId $member,
        private readonly AccountId $accountId,
        private readonly Provider $provider,
        private Email $externalAccount,
        private RefreshToken $refreshToken,
        private string $clientId,
        private \DateTimeImmutable $connectedAt,
    ) {
        $this->modifiedAt = $connectedAt;
    }

    /**
     * @throws IncompleteGrant
     */
    public static function establish(MemberId $member, AccountId $accountId, ClientCredentials $client, Grant $grant, \DateTimeImmutable $at): self
    {
        self::ensureComplete($grant);
        $connection = new self($member, $accountId, $client->provider, $grant->account, $grant->refreshToken, $client->id, $at);
        $connection->recordThat(new Connected($accountId, $member, $at));

        return $connection;
    }

    private static function ensureComplete(Grant $grant): void
    {
        if (!$grant->complete) {
            throw IncompleteGrant::ofTheCalendar();
        }
    }

    /**
     * Connected again: the former access to another account is revoked, the provider keeping it otherwise; that of
     * the same account is not, Google revoking with a token the whole grant of its account, the new token with it.
     *
     * @throws IncompleteGrant
     */
    public function renew(Grant $grant, ClientCredentials $client, ProviderAccess $access, \DateTimeImmutable $at): void
    {
        self::ensureComplete($grant);
        $this->revokeTheAccessToAnotherAccount($grant->account, $access);
        $this->externalAccount = $grant->account;
        $this->refreshToken = $grant->refreshToken;
        $this->clientId = $client->id;
        $this->state = ConnectionState::Connected;
        $this->connectedAt = $this->modifiedAt = $at;
        $this->recordThat(new Connected($this->accountId, $this->member, $at));
    }

    private function revokeTheAccessToAnotherAccount(Email $account, ProviderAccess $access): void
    {
        if (!$account->equals($this->externalAccount)) {
            $access->revoke($this->provider, $this->refreshToken);
        }
    }

    /**
     * Asks the provider, through the client of the account, whether it still grants the access: once it refuses, or
     * once the account has another client, the token being valid only with the one it was granted to, the member is to
     * connect again.
     */
    public function check(ClientCredentials $client, ProviderAccess $access, \DateTimeImmutable $at): void
    {
        if (ConnectionState::Connected !== $this->state || $this->isStillGrantedBy($client, $access)) {
            return;
        }

        $this->state = ConnectionState::ToReconnect;
        $this->modifiedAt = $at;
        $this->recordThat(new ConnectionLost($this->accountId, $this->member, $at));
    }

    private function isStillGrantedBy(ClientCredentials $client, ProviderAccess $access): bool
    {
        return $client->id === $this->clientId && $access->stillGrants($client, $this->refreshToken);
    }

    /**
     * The access is revoked at the provider before the connection is forgotten.
     */
    public function disconnect(ProviderAccess $access, \DateTimeImmutable $at): void
    {
        $access->revoke($this->provider, $this->refreshToken);
        $this->recordThat(new Disconnected($this->accountId, $this->member, $at));
    }

    public function member(): MemberId
    {
        return $this->member;
    }

    public function provider(): Provider
    {
        return $this->provider;
    }
}
