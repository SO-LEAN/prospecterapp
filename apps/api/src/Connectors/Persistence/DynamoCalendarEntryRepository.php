<?php

declare(strict_types=1);

namespace App\Connectors\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use Prospecter\Core\Connectors\Domain\Model\CalendarEntry;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\SyncState;
use Prospecter\Core\Connectors\Domain\Repository\CalendarEntryRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The exchanges pushed into the calendars of their holders, in the table of Connectors (ADR 0127):
 * `EXCHANGE#<exchange>` / `ENTRY#<provider>`, the entry as a document, listed under its member in `GSI1`
 * (`ENTRIES#<member>`, by the moment its exchange starts, ADR 0133), and for an exchange with a contact its link
 * `CONTACT#<person>` / `ENTRY#<exchange>#<provider>`, the same document, to find the entries of a contact forgotten.
 * An entry written before ADR 0133 has neither state nor moment: synced since its exchange was pushed; one written
 * before ADR 0175 invites nobody.
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

    public function ofMember(MemberId $member): array
    {
        return array_map(static fn (array $item): CalendarEntry => self::restore($item['entry']), $this->connectorsTable->query(ConnectorsKeys::entriesOf($member->value), Table::BY_GSI1));
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function restore(array $document): CalendarEntry
    {
        $startsAt = isset($document['startsAt']) ? Documents::momentOf($document['startsAt']) : new \DateTimeImmutable('@0');

        return Hydrator::restore(CalendarEntry::class, [
            'exchange' => new ExchangeId($document['exchange']),
            'accountId' => new AccountId($document['accountId']),
            'member' => new MemberId($document['member']),
            'provider' => Provider::from($document['provider']),
            'contact' => null === ($document['contact'] ?? null) ? null : new PersonId($document['contact']),
            'startsAt' => $startsAt,
            'state' => SyncState::from($document['state'] ?? SyncState::Synced->value),
            'reason' => $document['reason'] ?? null,
            'since' => isset($document['since']) ? Documents::momentOf($document['since']) : $startsAt,
            'invitesAGuest' => $document['invitesAGuest'] ?? false,
        ]);
    }

    public function save(CalendarEntry $entry): void
    {
        $document = self::documentOf($entry);
        $writes = [$this->connectorsTable->putting([
            'PK' => ConnectorsKeys::exchange($document['exchange']),
            'SK' => ConnectorsKeys::entry($document['provider']),
            'GSI1PK' => ConnectorsKeys::entriesOf($document['member']),
            'GSI1SK' => $document['startsAt'].'#'.ConnectorsKeys::entryOf($document['exchange'], $document['provider']),
            'entry' => $document,
        ])];

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
     * @return array{exchange: string, accountId: string, member: string, provider: string, contact: ?string, startsAt: string, state: string, reason: ?string, since: string, invitesAGuest: bool}
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
            'startsAt' => Documents::moment($state['startsAt']),
            'state' => $state['state']->value,
            'reason' => $state['reason'],
            'since' => Documents::moment($state['since']),
            'invitesAGuest' => $state['invitesAGuest'],
        ];
    }
}
