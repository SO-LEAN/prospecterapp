<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\PushExchange;

use Prospecter\Core\Connectors\Domain\Model\CalendarEntry;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\HeldExchange;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\ContactNames;
use Prospecter\Core\Connectors\Domain\Port\HeldExchanges;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Port\ProviderCalendar;
use Prospecter\Core\Connectors\Domain\Repository\CalendarEntryRepository;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reacts to each change of an exchange by reading it again as it is now, whatever the order the changes arrive in
 * (ADR 0127): planned, it is pushed into the calendar of its holder; cancelled or erased, it is withdrawn from it;
 * done or missed, it stays there as it was. Nothing is asked of the provider without a connection of the holder, nor
 * without the client of their account.
 */
final readonly class PushExchangeHandler implements EventHandler
{
    use PublishesEvents;

    private const Provider PROVIDER = Provider::Google;

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

    public function __invoke(ExchangeFactsChanged $fact): void
    {
        $id = new ExchangeId($fact->exchangeId);
        $accountId = new AccountId($fact->accountId);
        $exchange = $this->exchanges->find($accountId, $id);

        if (null !== $exchange && $exchange->belongsInTheCalendar()) {
            $this->push($exchange, $fact->occurredAt);
        } elseif (null === $exchange || $exchange->isCalledOff()) {
            $this->withdraw($id, $accountId, $exchange?->holder, $fact->occurredAt);
        }
    }

    private function push(HeldExchange $exchange, \DateTimeImmutable $at): void
    {
        $connection = $this->connections->find($exchange->holder, self::PROVIDER);
        $client = $this->clients->find($exchange->accountId, self::PROVIDER);
        if (null === $connection || null === $client) {
            return;
        }

        $holder = $this->members->get($exchange->holder);
        $connection->push($exchange->asCalendarEvent($this->contactNameOf($exchange), $holder->reminderMinutesBefore), $client->credentials(), $this->calendars, $at);
        $this->connections->save($connection);
        $this->entries->save(CalendarEntry::of($exchange, self::PROVIDER));
        $this->publishWhatHappenedTo($connection);
    }

    private function contactNameOf(HeldExchange $exchange): ?string
    {
        return null === $exchange->contact ? null : $this->contactNames->nameOf($exchange->contact, $exchange->holder);
    }

    /**
     * Erased, the exchange is withdrawn from the calendar it was pushed into, Prospecting no longer knowing its holder.
     */
    private function withdraw(ExchangeId $id, AccountId $accountId, ?MemberId $holder, \DateTimeImmutable $at): void
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
