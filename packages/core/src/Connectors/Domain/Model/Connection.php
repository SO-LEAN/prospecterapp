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
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root. It holds the account that a member connected at a provider and the lasting access that the
 * account gave. It also holds the client of the member's account that the access was granted to. Once the calendar
 * « Prospecter » is created, it holds that calendar too, and the member's exchanges are pushed into it. A member has
 * one connection per provider, and the connection is theirs only (ADR 0120, 0125, 0127).
 */
final class Connection implements ReleasesEvents
{
    use RecordsEvents;

    /** A reading lists at most this many messages, so that their events fit in one write (ADR 0154). */
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
    public static function establish(Gesture $gesture, ClientCredentials $client, Grant $grant): self
    {
        self::ensureComplete($grant);
        $connection = new self($gesture->by, $gesture->accountId, $client->provider, $grant->account, $grant->refreshToken, $client->id, $gesture->at);
        $connection->recordThat(new Connected($gesture->accountId, $gesture->by, $gesture->at));

        return $connection;
    }

    private static function ensureComplete(Grant $grant): void
    {
        if (!$grant->grants(Scope::Calendar)) {
            throw IncompleteGrant::ofTheCalendar();
        }
    }

    /**
     * Connects the member again. A former access to another account is revoked, because the provider would keep it
     * otherwise. A former access to the same account is not revoked: Google revokes the account's whole grant with one
     * token, and that would revoke the new token too.
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
     * The imported mailbox is the connected account's mailbox. Another account imports nothing until its member
     * activates the import again. The former mailbox's watch expires within its week, and its access is revoked.
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
     * Asks the provider, through the account's client, whether it still grants the access. The member must connect
     * again once the provider refuses. They must also connect again once the account has another client, because a
     * token is valid only with the client it was granted to.
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
     * Puts a planned exchange's event into the calendar « Prospecter ». The first push creates the calendar. A push
     * creates it again once its member deleted it (ADR 0127), and the upcoming exchanges are then pushed into it again
     * (ADR 0133). A video call may ask for a link. Then the link that the provider created is told to its exchange
     * (ADR 0137).
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
     * @return string|null the link that the provider created for the event
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
     * Removes a cancelled or erased exchange's event from the calendar « Prospecter ». There is nothing to remove
     * while nothing was pushed. There is nothing to remove either once the member deleted the calendar.
     *
     * @throws CalendarRefusedThePush
     */
    public function withdraw(ExchangeId $exchange, ClientCredentials $client, ProviderCalendar $calendars, \DateTimeImmutable $at): void
    {
        $this->takeOut($client, $at, static fn (RefreshToken $token, string $calendar) => $calendars->remove($client, $token, $calendar, $exchange));
    }

    /**
     * Withdraws the event as `withdraw()` does. The provider also tells the event's guest that it is cancelled.
     *
     * @throws CalendarRefusedThePush
     */
    public function cancel(ExchangeId $exchange, ClientCredentials $client, ProviderCalendar $calendars, \DateTimeImmutable $at): void
    {
        $this->takeOut($client, $at, static fn (RefreshToken $token, string $calendar) => $calendars->cancel($client, $token, $calendar, $exchange));
    }

    /**
     * @param \Closure(RefreshToken, string): void $removal
     *
     * @throws CalendarRefusedThePush
     */
    private function takeOut(ClientCredentials $client, \DateTimeImmutable $at, \Closure $removal): void
    {
        $calendar = $this->calendar;
        if (null === $calendar) {
            return;
        }

        $this->reachTheCalendar($client, $at, function () use ($removal, $calendar): void {
            try {
                $removal($this->refreshToken, $calendar);
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
     * Only a connected connection reaches its calendar, through the client that its token was granted to. A token of
     * another client makes the member connect again. So does a token that the provider no longer grants. Nothing is
     * tried again.
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
     * Watches the member's mailbox through their account's topic. Their e-mails are imported from the point where the
     * mailbox history stands at this moment (ADR 0154).
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
     * Renews an active import's watch for another week, through the account's current topic. A token that the
     * provider refuses makes the import to reconnect. So does a token that no longer grants the mailbox. An account
     * that no longer has a topic renews nothing, and the watch expires within its week.
     */
    public function renewMailWatch(ProviderClient $client, ProviderMailbox $mailbox, \DateTimeImmutable $at): void
    {
        $notifications = $client->mailboxNotifications();
        if (!$this->importsMail() || null === $notifications) {
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
     * Records a change that the mailbox notified. The connection keeps the furthest history point notified, whatever
     * order the notifications arrive in. It also keeps when the notification came. An inactive import records nothing
     * (ADR 0154).
     */
    public function noteMailboxChange(string $historyId, \DateTimeImmutable $at): void
    {
        if (!$this->importsMail()) {
            return;
        }

        $this->notifiedHistoryId = self::furthest($this->notifiedHistoryId, $historyId);
        $this->lastNotifiedAt = $this->modifiedAt = $at;
        $this->recordThat(new MailboxChanged($this->accountId, $this->member, $at));
    }

    /**
     * A mailbox's history points are numbers that grow with its changes. They can be longer than an integer.
     */
    private static function furthest(?string $known, string $notified): string
    {
        return null === $known || strlen($notified) > strlen($known) || (strlen($notified) === strlen($known) && strcmp($notified, $known) > 0) ? $notified : $known;
    }

    /**
     * Lists the messages added to the mailbox since the last history point read, so that each one is imported. A
     * reading that stops before the end keeps the moment of the last complete reading. If the history expires in the
     * meantime, the next reading resumes from before the first unread message (ADR 0154). A member who no longer
     * manages personal data imports nothing: their import is stopped.
     */
    public function readMailboxHistory(Member $reader, ClientCredentials $client, ProviderMailbox $mailbox, \DateTimeImmutable $at): void
    {
        if (!$this->importsMail()) {
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
     * The provider may no longer keep the history from the point last read. Then the reading resumes from the date
     * when the history was last read to its end.
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
     * Lists the messages that the mailbox exchanged with an address over the previous 12 months. It takes the 200
     * newest. Each one is imported as an added message is. This works only while the import is active. A token that
     * the provider refuses makes the import to reconnect.
     *
     * @throws MailImportNotActive
     */
    public function catchUpMailWith(Email $address, ClientCredentials $client, ProviderMailbox $mailbox, \DateTimeImmutable $at): void
    {
        if (!$this->importsMail()) {
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
     * Gets a mailbox message to import. It returns none once the import is no longer active. It returns none either
     * once the provider refuses the access: the next history reading then makes the import to reconnect.
     */
    public function mailOf(string $messageId, ClientCredentials $client, ProviderMailbox $mailbox): ?MailboxMail
    {
        if (!$this->importsMail()) {
            return null;
        }

        try {
            return $mailbox->message($client, $this->refreshToken, $messageId);
        } catch (AccessRevoked) {
            return null;
        }
    }

    /**
     * Sends each attachment of an e-mail to import to the private storage, once for each of the e-mail's
     * correspondents. It returns each attachment's uploads, in the attachments' order (ADR 0182).
     *
     * @return list<list<string>>
     *
     * @throws AccessRevoked
     */
    public function depositAttachmentsOf(MailboxMail $mail, int $correspondents, ClientCredentials $client, ProviderMailbox $mailbox): array
    {
        return array_map(fn (MailboxAttachment $attachment): array => $mailbox->depositAttachment($client, $this->refreshToken, $mail->providerId, $attachment, $correspondents), $mail->attachmentsToImport());
    }

    /**
     * The imported mailbox is the connected account's mailbox.
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
     * Stops the mailbox from notifying Prospecter. The watch is stopped at the provider through the account's client.
     * Without a client, nothing is asked of the provider, and the watch expires within its week. There is nothing to
     * stop while the import is off.
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
     * The mailbox notifies Prospecter and is read. The import is neither off nor to reconnect.
     */
    public function importsMail(): bool
    {
        return MailImport::Active === $this->mailImport;
    }

    /**
     * A connected connection reached its calendar at its last push. A connection to reconnect reached nothing.
     */
    public function isConnected(): bool
    {
        return ConnectionState::Connected === $this->state;
    }

    /**
     * Two connections are the same when they belong to the same member and were connected at the same moment. A
     * connection connected again is another connection.
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
