<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\Connectors\Domain\Event\CalendarRecreated;
use Prospecter\Core\Connectors\Domain\Event\Connected;
use Prospecter\Core\Connectors\Domain\Event\ConnectionLost;
use Prospecter\Core\Connectors\Domain\Event\Disconnected;
use Prospecter\Core\Connectors\Domain\Event\MailboxChanged;
use Prospecter\Core\Connectors\Domain\Event\MailboxMessageAdded;
use Prospecter\Core\Connectors\Domain\Event\MailImportLost;
use Prospecter\Core\Connectors\Domain\Event\MailImportStarted;
use Prospecter\Core\Connectors\Domain\Event\MailImportStopped;
use Prospecter\Core\Connectors\Domain\Event\MailWatchRenewed;
use Prospecter\Core\Connectors\Domain\Event\VideoCallLinkCreated;
use Prospecter\Core\Connectors\Domain\Exception\AccessRevoked;
use Prospecter\Core\Connectors\Domain\Exception\CalendarGone;
use Prospecter\Core\Connectors\Domain\Exception\CalendarRefusedThePush;
use Prospecter\Core\Connectors\Domain\Exception\HistoryExpired;
use Prospecter\Core\Connectors\Domain\Exception\IncompleteGrant;
use Prospecter\Core\Connectors\Domain\Exception\MailImportNotActive;
use Prospecter\Core\Connectors\Domain\Port\ProviderAccess;
use Prospecter\Core\Connectors\Domain\Port\ProviderCalendar;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;
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

    /** the most messages a reading lists, so that their events fit in a write (ADR 0154) */
    private const int MOST_MESSAGES_BY_HISTORY = 500;
    private const int MOST_MESSAGES_BY_DATE = 1000;
    private const int MOST_MESSAGES_CAUGHT_UP = 200;
    private const string CATCH_UP_SINCE = '-12 months';

    private ConnectionState $state = ConnectionState::Connected;
    private ?string $calendar = null;
    private MailImport $mailImport = MailImport::Off;
    private ?string $historyId = null;
    private ?\DateTimeImmutable $watchExpiresAt = null;
    private ?string $notifiedHistoryId = null;
    private ?\DateTimeImmutable $lastNotifiedAt = null;
    private ?\DateTimeImmutable $historyReadAt = null;
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
        if (!$grant->grants(Scope::Calendar)) {
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
        $this->forgetTheMailImportOfAnotherAccount($grant->account, $at);
        $this->revokeTheAccessToAnotherAccount($grant->account, $access);
        $this->externalAccount = $grant->account;
        $this->refreshToken = $grant->refreshToken;
        $this->clientId = $client->id;
        $this->state = ConnectionState::Connected;
        $this->connectedAt = $this->modifiedAt = $at;
        $this->recordThat(new Connected($this->accountId, $this->member, $at));
    }

    /**
     * The mailbox imported is that of the account connected: another account imports nothing until its member
     * activates the import again, the watch of the former mailbox expiring within its week, its access revoked.
     */
    private function forgetTheMailImportOfAnotherAccount(Email $account, \DateTimeImmutable $at): void
    {
        if (MailImport::Off === $this->mailImport || $account->equals($this->externalAccount)) {
            return;
        }

        $this->forgetTheMailImport($at);
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
     * once its member deleted it (ADR 0127), its exchanges to come to push into it again (ADR 0133); the link the
     * provider created for a video call asking one told to its exchange (ADR 0137).
     *
     * @throws CalendarRefusedThePush
     */
    public function push(CalendarEvent $event, ClientCredentials $client, ProviderCalendar $calendars, \DateTimeImmutable $at): void
    {
        $this->reachTheCalendar($client, $at, function () use ($event, $client, $calendars, $at): void {
            $this->tellTheLinkCreated($event, $this->put($event, $client, $calendars, $at), $at);
        });
    }

    /**
     * @return string|null the link the provider created for the event
     *
     * @throws AccessRevoked|CalendarRefusedThePush
     */
    private function put(CalendarEvent $event, ClientCredentials $client, ProviderCalendar $calendars, \DateTimeImmutable $at): ?string
    {
        try {
            return $calendars->put($client, $this->refreshToken, $this->calendarIn($calendars, $client, $at), $event);
        } catch (CalendarGone) {
            $this->calendar = null;
            $link = $calendars->put($client, $this->refreshToken, $this->calendarIn($calendars, $client, $at), $event);
            $this->recordThat(new CalendarRecreated($this->accountId, $this->member, $at));

            return $link;
        }
    }

    private function tellTheLinkCreated(CalendarEvent $event, ?string $link, \DateTimeImmutable $at): void
    {
        if (null !== $link) {
            $this->recordThat(new VideoCallLinkCreated($this->accountId, $this->member, $event->exchange, $link, $at));
        }
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
     * The mailbox of the member watched through the topic of their account, their e-mails imported from where its
     * history stands at this moment (ADR 0154).
     *
     * @throws AccessRevoked
     */
    public function importMail(ClientCredentials $client, MailboxNotifications $notifications, ProviderMailbox $mailbox, \DateTimeImmutable $at): void
    {
        $watch = $mailbox->watch($client, $this->refreshToken, $notifications->topic);
        $this->mailImport = MailImport::Active;
        $this->historyId = $watch->historyId;
        $this->watchExpiresAt = $watch->expiresAt;
        $this->historyReadAt = $this->modifiedAt = $at;
        $this->recordThat(new MailImportStarted($this->accountId, $this->member, $at));
    }

    /**
     * The watch of an import active renewed for another week, through the topic the account has now; a token the
     * provider refuses, or that no longer grants the mailbox, leaves the import to reconnect; an account without its
     * topic any more renews nothing, the watch expiring within its week.
     */
    public function renewMailWatch(ProviderClient $client, ProviderMailbox $mailbox, \DateTimeImmutable $at): void
    {
        $notifications = $client->mailboxNotifications();
        if (MailImport::Active !== $this->mailImport || null === $notifications) {
            return;
        }

        try {
            $this->watchExpiresAt = $mailbox->watch($client->credentials(), $this->refreshToken, $notifications->topic)->expiresAt;
            $this->modifiedAt = $at;
            $this->recordThat(new MailWatchRenewed($this->accountId, $this->member, $at));
        } catch (AccessRevoked) {
            $this->loseTheMailImport($at);
        }
    }

    /**
     * The mailbox notified a change: the furthest point of its history notified kept, whatever the order the
     * notifications arrive in, and when; an import not active notes nothing (ADR 0154).
     */
    public function noteMailboxChange(string $historyId, \DateTimeImmutable $at): void
    {
        if (MailImport::Active !== $this->mailImport) {
            return;
        }

        $this->notifiedHistoryId = self::furthest($this->notifiedHistoryId, $historyId);
        $this->lastNotifiedAt = $this->modifiedAt = $at;
        $this->recordThat(new MailboxChanged($this->accountId, $this->member, $at));
    }

    /**
     * The points of the history of a mailbox are numbers growing with its changes, longer than an integer may be.
     */
    private static function furthest(?string $known, string $notified): string
    {
        return null === $known || strlen($notified) > strlen($known) || (strlen($notified) === strlen($known) && strcmp($notified, $known) > 0) ? $notified : $known;
    }

    /**
     * The messages added to the mailbox since the point of its history read last, each to import; a reading stopped
     * before the end leaves the moment of the last complete one, so that the history expiring meanwhile is resumed from
     * before the first message left unread (ADR 0154). A member no longer managing the personal data imports nothing:
     * their import is stopped.
     */
    public function readMailboxHistory(Member $reader, ClientCredentials $client, ProviderMailbox $mailbox, \DateTimeImmutable $at): void
    {
        if (MailImport::Active !== $this->mailImport) {
            return;
        }

        if (!$reader->managesPersonalData) {
            $this->stopMailImport($client, $mailbox, $at);

            return;
        }

        try {
            $changes = $this->changesOf($client, $mailbox);
        } catch (AccessRevoked) {
            $this->loseTheMailImport($at);

            return;
        }

        foreach ($changes->messageIds as $messageId) {
            $this->recordThat(new MailboxMessageAdded($this->accountId, $this->member, $messageId, $at));
        }

        $this->historyId = $changes->historyId;
        $this->historyReadAt = $changes->complete ? $at : $this->historyReadAt;
        $this->modifiedAt = $at;
    }

    /**
     * The history the provider no longer keeps from where it was read is resumed by the date it was read completely.
     *
     * @throws AccessRevoked
     */
    private function changesOf(ClientCredentials $client, ProviderMailbox $mailbox): MailboxChanges
    {
        try {
            return $mailbox->messagesAddedSince($client, $this->refreshToken, (string) $this->historyId, self::MOST_MESSAGES_BY_HISTORY);
        } catch (HistoryExpired) {
            return $mailbox->messagesExchangedAfter($client, $this->refreshToken, $this->historyReadAt ?? $this->connectedAt, self::MOST_MESSAGES_BY_DATE);
        }
    }

    /**
     * The messages the mailbox exchanged with an address of the 12 months before, each to import as the messages
     * added to it are, the 200 newest first; only while the import is active, a token the provider refuses making it
     * to reconnect.
     *
     * @throws MailImportNotActive
     */
    public function catchUpMailWith(Email $address, ClientCredentials $client, ProviderMailbox $mailbox, \DateTimeImmutable $at): void
    {
        if (MailImport::Active !== $this->mailImport) {
            throw MailImportNotActive::toCatchUp();
        }

        try {
            $messageIds = $mailbox->messagesExchangedWith($client, $this->refreshToken, $address, $at->modify(self::CATCH_UP_SINCE), self::MOST_MESSAGES_CAUGHT_UP);
        } catch (AccessRevoked) {
            $this->loseTheMailImport($at);

            return;
        }

        foreach ($messageIds as $messageId) {
            $this->recordThat(new MailboxMessageAdded($this->accountId, $this->member, $messageId, $at));
        }
    }

    /**
     * A message of the mailbox imported, none once the import is no longer active, nor once the provider refuses the
     * access: the next reading of the history makes the import to reconnect.
     */
    public function mailOf(string $messageId, ClientCredentials $client, ProviderMailbox $mailbox): ?MailboxMail
    {
        if (MailImport::Active !== $this->mailImport) {
            return null;
        }

        try {
            return $mailbox->message($client, $this->refreshToken, $messageId);
        } catch (AccessRevoked) {
            return null;
        }
    }

    /**
     * The mailbox imported: that of the account connected.
     */
    public function mailbox(): Email
    {
        return $this->externalAccount;
    }

    private function loseTheMailImport(\DateTimeImmutable $at): void
    {
        $this->mailImport = MailImport::ToReconnect;
        $this->modifiedAt = $at;
        $this->recordThat(new MailImportLost($this->accountId, $this->member, $at));
    }

    /**
     * The mailbox no longer notifies Prospecter: its watch stopped at the provider through the client of the account,
     * nothing asked of it without one, the watch expiring within its week; nothing to stop while the import is off.
     */
    public function stopMailImport(?ClientCredentials $client, ProviderMailbox $mailbox, \DateTimeImmutable $at): void
    {
        if (MailImport::Off === $this->mailImport) {
            return;
        }

        if (null !== $client) {
            $mailbox->stop($client, $this->refreshToken);
        }

        $this->forgetTheMailImport($at);
    }

    private function forgetTheMailImport(\DateTimeImmutable $at): void
    {
        $this->mailImport = MailImport::Off;
        $this->historyId = $this->notifiedHistoryId = null;
        $this->watchExpiresAt = $this->lastNotifiedAt = $this->historyReadAt = null;
        $this->modifiedAt = $at;
        $this->recordThat(new MailImportStopped($this->accountId, $this->member, $at));
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

    /**
     * The same connection: of the same member, connected at the same moment; connected again, it is another.
     */
    public function isTheSameAs(self $other): bool
    {
        return $this->member->equals($other->member) && $this->connectedAt == $other->connectedAt;
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
