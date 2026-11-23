<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application;

use Prospecter\Core\Connectors\Domain\Exception\CalendarRefusedThePush;
use Prospecter\Core\Connectors\Domain\Model\CalendarEntry;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\Guest;
use Prospecter\Core\Connectors\Domain\Model\HeldExchange;
use Prospecter\Core\Connectors\Domain\Model\Member;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\ContactNames;
use Prospecter\Core\Connectors\Domain\Port\HeldExchanges;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Port\ProviderCalendar;
use Prospecter\Core\Connectors\Domain\Repository\CalendarEntryRepository;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Puts an exchange into its holder's calendar at the provider, or withdraws it. The reactions and the retries push
 * through it (ADR 0127, 0133). Nothing is asked of the provider when the holder has no connection, or when their
 * account has no client. An entry keeps each exchange's push state. A refusal from the provider is kept there rather
 * than thrown. A connection that its member disconnected in the meantime is never written again.
 */
final readonly class CalendarPusher
{
    use PublishesEvents;

    private const Provider PROVIDER = Provider::Google;
    private const string REFUSED = 'calendarRefusedThePush';

    public function __construct(
        private HeldExchanges $exchanges,
        private CalendarEntryRepository $entries,
        private ConnectionRepository $connections,
        private ProviderClientRepository $clients,
        private Members $members,
        private ContactNames $contactNames,
        private ProviderCalendar $calendars,
        private Events $events,
    ) {
    }

    /**
     * Reads the exchange again as it is now, whatever the order its changes arrive in (ADR 0127). A planned exchange is
     * pushed. A cancelled or erased exchange is withdrawn. A done or missed exchange stays as it was.
     */
    public function follow(ExchangeId $id, AccountId $accountId, \DateTimeImmutable $at): void
    {
        $exchange = $this->exchanges->find($accountId, $id);

        if (null !== $exchange && $exchange->belongsInTheCalendar()) {
            $this->push($exchange, $at);
        } elseif (null === $exchange || $exchange->isCalledOff()) {
            $this->withdraw($id, $accountId, $exchange?->holder, $at);
        }
    }

    /**
     * The event names its contact as the holder reads them. It names nobody once the contact is forgotten.
     */
    public function push(HeldExchange $exchange, \DateTimeImmutable $at): void
    {
        $connection = $this->connections->find($exchange->holder, self::PROVIDER);
        $client = $this->clients->find($exchange->accountId, self::PROVIDER);
        if (null === $connection || null === $client) {
            return;
        }

        $entry = $this->entries->find($exchange->id);
        try {
            $this->putInto($connection, $exchange, $entry, $client->credentials(), $at);
            $this->keepThePushOf($exchange, $entry, $connection, $at);
        } catch (CalendarRefusedThePush) {
            $this->keepTheRefusal($entry ?? CalendarEntry::of($exchange, self::PROVIDER, $at), $at);
        }

        $this->connections->replace($connection);
        $this->publishWhatHappenedTo($connection);
    }

    private function putInto(Connection $connection, HeldExchange $exchange, ?CalendarEntry $entry, ClientCredentials $client, \DateTimeImmutable $at): void
    {
        $holder = $this->members->get($exchange->holder);
        $connection->push($exchange->asCalendarEvent($this->contactNameOf($exchange), $holder->reminderMinutesBefore, $this->guestOf($exchange, $holder, $entry)), $client, $this->calendars, $at);
    }

    /**
     * The guest is the contact who booked the exchange, at their e-mail as its holder reads it. A contact without an
     * e-mail is no guest. For a call, the guest also holds the phone number that the holder calls them at. The push
     * tells the guest only when the event is new to them or has moved.
     */
    private function guestOf(HeldExchange $exchange, Member $holder, ?CalendarEntry $entry): ?Guest
    {
        if (null === $exchange->contact || !$exchange->invitesItsContact()) {
            return null;
        }

        $details = $this->contactNames->contactDetailsOf($exchange->contact, $exchange->holder);
        if (null === $details?->email) {
            return null;
        }

        $isTold = $entry?->tellsItsGuestOfAPushOf($exchange) ?? true;

        return $exchange->isACall()
            ? new Guest($details->email, $details->phoneNumber, $holder->firstName, $isTold)
            : new Guest($details->email, isTold: $isTold);
    }

    private function contactNameOf(HeldExchange $exchange): ?string
    {
        return null === $exchange->contact ? null : $this->contactNames->nameOf($exchange->contact, $exchange->holder);
    }

    /**
     * The entry is synced once the push reached the calendar. A connection to reconnect reached nothing. Then an
     * exchange already pushed waits for its change to be pushed.
     */
    private function keepThePushOf(HeldExchange $exchange, ?CalendarEntry $entry, Connection $connection, \DateTimeImmutable $at): void
    {
        if ($connection->isConnected()) {
            $entry ??= CalendarEntry::of($exchange, self::PROVIDER, $at);
            $entry->pushed($exchange, $at);
            $this->entries->save($entry);
        } elseif (null !== $entry) {
            $entry->awaits($at);
            $this->entries->save($entry);
        }
    }

    private function keepTheRefusal(CalendarEntry $entry, \DateTimeImmutable $at): void
    {
        $entry->refused(self::REFUSED, $at);
        $this->entries->save($entry);
    }

    /**
     * Withdraws an erased exchange from the calendar it was pushed into. Prospecting may no longer know its holder, so
     * the entry names the holder. The entry is forgotten, unless the provider refused the withdrawal.
     */
    public function withdraw(ExchangeId $id, AccountId $accountId, ?MemberId $holder, \DateTimeImmutable $at): void
    {
        $entry = $this->entries->find($id);
        $member = $holder ?? $entry?->member();
        if (null === $member) {
            return;
        }

        $connection = $this->connections->find($member, self::PROVIDER);
        $client = $this->clients->find($accountId, self::PROVIDER);
        if (null === $connection || null === $client) {
            $this->forget($entry);

            return;
        }

        $this->withdrawFrom($connection, $id, $client->credentials(), $entry, $at);
        $this->connections->replace($connection);
        $this->publishWhatHappenedTo($connection);
    }

    private function withdrawFrom(Connection $connection, ExchangeId $id, ClientCredentials $client, ?CalendarEntry $entry, \DateTimeImmutable $at): void
    {
        try {
            $this->takeOut($connection, $id, $client, $entry, $at);
            $this->forget($entry);
        } catch (CalendarRefusedThePush $refusal) {
            if (null === $entry) {
                throw $refusal;
            }

            $this->keepTheRefusal($entry, $at);
        }
    }

    /**
     * An upcoming event that invites a guest is cancelled for them. Any other event is removed silently.
     */
    private function takeOut(Connection $connection, ExchangeId $id, ClientCredentials $client, ?CalendarEntry $entry, \DateTimeImmutable $at): void
    {
        if (true === $entry?->invitesAGuestStillToComeAt($at)) {
            $connection->cancel($id, $client, $this->calendars, $at);
        } else {
            $connection->withdraw($id, $client, $this->calendars, $at);
        }
    }

    private function forget(?CalendarEntry $entry): void
    {
        if (null !== $entry) {
            $this->entries->remove($entry);
        }
    }
}
