<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application;

use Prospecter\Core\Connectors\Domain\Model\CalendarEntry;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\HeldExchange;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\ContactNames;
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
 * Puts an exchange into the calendar of its holder at the provider, or withdraws it, for the reactions that push
 * (ADR 0127): nothing is asked of the provider without a connection of the holder, nor without the client of their
 * account; an entry is kept of each exchange the push reached the calendar with.
 */
final readonly class CalendarPusher
{
    use PublishesEvents;

    private const Provider PROVIDER = Provider::Google;

    public function __construct(
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
     * Its contact named as its holder reads them, none once forgotten.
     */
    public function push(HeldExchange $exchange, \DateTimeImmutable $at): void
    {
        $connection = $this->connections->find($exchange->holder, self::PROVIDER);
        $client = $this->clients->find($exchange->accountId, self::PROVIDER);
        if (null === $connection || null === $client) {
            return;
        }

        $holder = $this->members->get($exchange->holder);
        $connection->push($exchange->asCalendarEvent($this->contactNameOf($exchange), $holder->reminderMinutesBefore), $client->credentials(), $this->calendars, $at);
        $this->connections->save($connection);
        $this->keepTheEntryOf($exchange, $connection);
        $this->publishWhatHappenedTo($connection);
    }

    /**
     * Only an exchange the push reached the calendar with: a connection to reconnect pushed nothing.
     */
    private function keepTheEntryOf(HeldExchange $exchange, Connection $connection): void
    {
        if ($connection->isConnected()) {
            $this->entries->save(CalendarEntry::of($exchange, self::PROVIDER));
        }
    }

    private function contactNameOf(HeldExchange $exchange): ?string
    {
        return null === $exchange->contact ? null : $this->contactNames->nameOf($exchange->contact, $exchange->holder);
    }

    /**
     * Erased, the exchange is withdrawn from the calendar it was pushed into, Prospecting no longer knowing its holder.
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
        if (null !== $connection && null !== $client) {
            $connection->withdraw($id, $client->credentials(), $this->calendars, $at);
            $this->connections->save($connection);
            $this->publishWhatHappenedTo($connection);
        }

        if (null !== $entry) {
            $this->entries->remove($entry);
        }
    }
}
