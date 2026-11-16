<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\Connectors\Domain\Event\CalendarRecreated;
use Prospecter\Core\Connectors\Domain\Event\Connected;
use Prospecter\Core\Connectors\Domain\Event\ConnectionLost;
use Prospecter\Core\Connectors\Domain\Event\Disconnected;
use Prospecter\Core\Connectors\Domain\Exception\AccessRevoked;
use Prospecter\Core\Connectors\Domain\Exception\CalendarGone;
use Prospecter\Core\Connectors\Domain\Exception\CalendarRefusedThePush;
use Prospecter\Core\Connectors\Domain\Exception\IncompleteGrant;
use Prospecter\Core\Connectors\Domain\Port\ProviderAccess;
use Prospecter\Core\Connectors\Domain\Port\ProviderCalendar;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: the account a member connected at a provider, the lasting access it gave, and the client of their
 * account it was granted to, and the calendar « Prospecter » its exchanges are pushed into once created; one per
 * member and provider, theirs only (ADR 0120, 0125, 0127).
 */
final class Connection implements ReleasesEvents
{
    use RecordsEvents;

    private ConnectionState $state = ConnectionState::Connected;
    private ?string $calendar = null;
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

        $this->lose($at);
    }

    private function lose(\DateTimeImmutable $at): void
    {
        $this->state = ConnectionState::ToReconnect;
        $this->modifiedAt = $at;
        $this->recordThat(new ConnectionLost($this->accountId, $this->member, $at));
    }

    private function isStillGrantedBy(ClientCredentials $client, ProviderAccess $access): bool
    {
        return $client->id === $this->clientId && $access->stillGrants($client, $this->refreshToken);
    }

    /**
     * The event of a planned exchange put into the calendar « Prospecter », created at the first push, and again
     * once its member deleted it (ADR 0127), its exchanges to come to push into it again (ADR 0133).
     *
     * @throws CalendarRefusedThePush
     */
    public function push(CalendarEvent $event, ClientCredentials $client, ProviderCalendar $calendars, \DateTimeImmutable $at): void
    {
        $this->reachTheCalendar($client, $at, function () use ($event, $client, $calendars, $at): void {
            try {
                $calendars->put($client, $this->refreshToken, $this->calendarIn($calendars, $client, $at), $event);
            } catch (CalendarGone) {
                $this->calendar = null;
                $calendars->put($client, $this->refreshToken, $this->calendarIn($calendars, $client, $at), $event);
                $this->recordThat(new CalendarRecreated($this->accountId, $this->member, $at));
            }
        });
    }

    /**
     * The event of an exchange cancelled or erased removed from the calendar « Prospecter »: none to remove while
     * nothing was pushed, nor once its member deleted the calendar.
     *
     * @throws CalendarRefusedThePush
     */
    public function withdraw(ExchangeId $exchange, ClientCredentials $client, ProviderCalendar $calendars, \DateTimeImmutable $at): void
    {
        $calendar = $this->calendar;
        if (null === $calendar) {
            return;
        }

        $this->reachTheCalendar($client, $at, function () use ($exchange, $client, $calendars, $calendar): void {
            try {
                $calendars->remove($client, $this->refreshToken, $calendar, $exchange);
            } catch (CalendarGone) {
                $this->calendar = null;
            }
        });
    }

    private function calendarIn(ProviderCalendar $calendars, ClientCredentials $client, \DateTimeImmutable $at): string
    {
        if (null === $this->calendar) {
            $this->calendar = $calendars->create($client, $this->refreshToken);
            $this->modifiedAt = $at;
        }

        return $this->calendar;
    }

    /**
     * Only a connection still connected reaches its calendar, through the client its token was granted to: a token
     * of another client, or one the provider no longer grants, makes the member connect again, nothing tried again.
     *
     * @param \Closure(): void $call
     */
    private function reachTheCalendar(ClientCredentials $client, \DateTimeImmutable $at, \Closure $call): void
    {
        if (ConnectionState::Connected !== $this->state) {
            return;
        }

        if ($client->id !== $this->clientId) {
            $this->lose($at);

            return;
        }

        try {
            $call();
        } catch (AccessRevoked) {
            $this->lose($at);
        }
    }

    /**
     * The access is revoked at the provider before the connection is forgotten.
     */
    public function disconnect(ProviderAccess $access, \DateTimeImmutable $at): void
    {
        $access->revoke($this->provider, $this->refreshToken);
        $this->recordThat(new Disconnected($this->accountId, $this->member, $at));
    }

    /**
     * Still connected, it reached its calendar at its last push; to reconnect, it reached nothing.
     */
    public function isConnected(): bool
    {
        return ConnectionState::Connected === $this->state;
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
