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
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The exchanges pushed into the calendars of their holders, in the table of Connectors (ADR 0127):
 * `EXCHANGE#<exchange>` / `ENTRY#<provider>`, the entry as a document, and for an exchange with a contact its link
 * `CONTACT#<person>` / `ENTRY#<exchange>#<provider>`, the same document, to find the entries of a contact forgotten.
 */
final readonly class DynamoCalendarEntryRepository implements CalendarEntryRepository
{
    public function __construct(private Table $connectorsTable)
    {
    }

    public function find(ExchangeId $exchange): ?CalendarEntry
    {
        $item = $this->connectorsTable->query(ConnectorsKeys::exchange($exchange->value))[0] ?? null;

        return null === $item ? null : self::restore($item['entry']);
    }

    public function ofContact(PersonId $contact): array
    {
        return array_map(static fn (array $item): CalendarEntry => self::restore($item['entry']), $this->connectorsTable->query(ConnectorsKeys::contact($contact->value)));
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function restore(array $document): CalendarEntry
    {
        return Hydrator::restore(CalendarEntry::class, [
            'exchange' => new ExchangeId($document['exchange']),
            'accountId' => new AccountId($document['accountId']),
            'member' => new MemberId($document['member']),
            'provider' => Provider::from($document['provider']),
            'contact' => null === ($document['contact'] ?? null) ? null : new PersonId($document['contact']),
        ]);
    }

    public function save(CalendarEntry $entry): void
    {
        $document = self::documentOf($entry);
        $writes = [$this->connectorsTable->putting(['PK' => ConnectorsKeys::exchange($document['exchange']), 'SK' => ConnectorsKeys::entry($document['provider']), 'entry' => $document])];

        if (null !== $document['contact']) {
            $writes[] = $this->connectorsTable->putting(['PK' => ConnectorsKeys::contact($document['contact']), 'SK' => ConnectorsKeys::entryOf($document['exchange'], $document['provider']), 'entry' => $document]);
        }

        $this->connectorsTable->transact($writes);
    }

    public function remove(CalendarEntry $entry): void
    {
        $document = self::documentOf($entry);
        $writes = [$this->connectorsTable->deleting(ConnectorsKeys::exchange($document['exchange']), ConnectorsKeys::entry($document['provider']))];

        if (null !== $document['contact']) {
            $writes[] = $this->connectorsTable->deleting(ConnectorsKeys::contact($document['contact']), ConnectorsKeys::entryOf($document['exchange'], $document['provider']));
        }

        $this->connectorsTable->transact($writes);
    }

    /**
     * @return array{exchange: string, accountId: string, member: string, provider: string, contact: ?string}
     */
    private static function documentOf(CalendarEntry $entry): array
    {
        $state = Hydrator::stateOf($entry);

        return [
            'exchange' => (string) $state['exchange'],
            'accountId' => (string) $state['accountId'],
            'member' => (string) $state['member'],
            'provider' => $state['provider']->value,
            'contact' => $state['contact']?->value,
        ];
    }
}
