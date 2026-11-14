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
 * Aggregate root: the account a member connected at a provider, and the lasting access it gave; one per member and
 * provider, theirs only (ADR 0120).
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
        private \DateTimeImmutable $connectedAt,
    ) {
        $this->modifiedAt = $connectedAt;
    }

    /**
     * @throws IncompleteGrant
     */
    public static function establish(MemberId $member, AccountId $accountId, Provider $provider, Grant $grant, \DateTimeImmutable $at): self
    {
        self::ensureComplete($grant);
        $connection = new self($member, $accountId, $provider, $grant->account, $grant->refreshToken, $at);
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
     * Connected again, maybe to another account: the former access is revoked, the provider keeping it otherwise.
     *
     * @throws IncompleteGrant
     */
    public function renew(Grant $grant, ProviderAccess $access, \DateTimeImmutable $at): void
    {
        self::ensureComplete($grant);
        $access->revoke($this->provider, $this->refreshToken);
        $this->externalAccount = $grant->account;
        $this->refreshToken = $grant->refreshToken;
        $this->state = ConnectionState::Connected;
        $this->connectedAt = $this->modifiedAt = $at;
        $this->recordThat(new Connected($this->accountId, $this->member, $at));
    }

    /**
     * Asks the provider whether it still grants the access: once it refuses, the member is to connect again.
     */
    public function check(ProviderAccess $access, \DateTimeImmutable $at): void
    {
        if (ConnectionState::Connected !== $this->state || $access->stillGrants($this->provider, $this->refreshToken)) {
            return;
        }

        $this->state = ConnectionState::ToReconnect;
        $this->modifiedAt = $at;
        $this->recordThat(new ConnectionLost($this->accountId, $this->member, $at));
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
