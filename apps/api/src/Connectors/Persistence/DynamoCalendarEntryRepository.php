<?php

namespace App\Connectors\Persistence;

use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use Prospecter\Core\Connectors\Domain\Model\CalendarEntry;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Repository\CalendarEntryRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The exchanges pushed into the calendars of their holders, in the table of Connectors (ADR 0127):
 * `EXCHANGE#<exchange>` / `ENTRY#<provider>`, the entry as a document.
 */
final readonly class DynamoCalendarEntryRepository implements CalendarEntryRepository
{
    public function __construct(private Table $connectorsTable)
    {
    }

    public function find(ExchangeId $exchange): ?CalendarEntry
    {
        $item = $this->connectorsTable->query(ConnectorsKeys::exchange($exchange->value))[0] ?? null;

        return null === $item ? null : Hydrator::restore(CalendarEntry::class, [
            'exchange' => $exchange,
            'accountId' => new AccountId($item['entry']['accountId']),
            'member' => new MemberId($item['entry']['member']),
            'provider' => Provider::from($item['entry']['provider']),
        ]);
    }

    public function save(CalendarEntry $entry): void
    {
        $state = Hydrator::stateOf($entry);

        $this->connectorsTable->transact([$this->connectorsTable->putting([
            'PK' => ConnectorsKeys::exchange($entry->exchange()->value),
            'SK' => ConnectorsKeys::entry($entry->provider()->value),
            'entry' => [
                'accountId' => (string) $state['accountId'],
                'member' => (string) $state['member'],
                'provider' => $state['provider']->value,
            ],
        ])]);
    }

    public function remove(CalendarEntry $entry): void
    {
        $this->connectorsTable->transact([$this->connectorsTable->deleting(ConnectorsKeys::exchange($entry->exchange()->value), ConnectorsKeys::entry($entry->provider()->value))]);
    }
}
