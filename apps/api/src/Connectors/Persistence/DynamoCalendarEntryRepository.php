<?php

declare(strict_types=1);

namespace App\Connectors\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Connectors\Domain\Model\CalendarEntry;
use Prospecter\Core\Connectors\Domain\Model\ContactId;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\SyncState;
use Prospecter\Core\Connectors\Domain\Repository\CalendarEntryRepository;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The exchanges pushed into their holders' calendars, in the Connectors table (ADR 0127). The key is
 * `EXCHANGE#<exchange>` / `ENTRY#<provider>`, and the item holds the entry as a document. The entry is listed under its
 * member in `GSI1` (`ENTRIES#<member>`, by its exchange's start, ADR 0133). An exchange with a contact also has a link
 * item, `CONTACT#<person>` / `ENTRY#<exchange>#<provider>`, with the same document. The link finds a forgotten
 * contact's entries.
 *
 * An entry written before ADR 0133 has no state and no moment. It reads as synced since its exchange was pushed. An
 * entry written before ADR 0175 invites nobody.
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

    public function ofContact(ContactId $contact): array
    {
        return array_map(static fn (array $item): CalendarEntry => self::restore($item['entry']), $this->connectorsTable->query(ConnectorsKeys::contact($contact->value)));
    }

    public function ofMember(MemberId $member): array
    {
        return array_map(static fn (array $item): CalendarEntry => self::restore($item['entry']), $this->connectorsTable->query(ConnectorsKeys::entriesOf($member->value), Table::BY_GSI1));
    }

    private static function restore(mixed $document): CalendarEntry
    {
        $document = Typed::document($document);
        $startsAt = isset($document['startsAt']) ? Documents::momentOf(Typed::string($document['startsAt'])) : new \DateTimeImmutable('@0');
        $contact = Typed::stringOrNull($document['contact'] ?? null);

        return Hydrator::restore(CalendarEntry::class, [
            'exchange' => new ExchangeId(Typed::string($document['exchange'])),
            'accountId' => new AccountId(Typed::string($document['accountId'])),
            'member' => new MemberId(Typed::string($document['member'])),
            'provider' => Provider::from(Typed::string($document['provider'])),
            'contact' => null === $contact ? null : new ContactId($contact),
            'startsAt' => $startsAt,
            'state' => SyncState::from(Typed::stringOrNull($document['state'] ?? null) ?? SyncState::Synced->value),
            'reason' => Typed::stringOrNull($document['reason'] ?? null),
            'since' => isset($document['since']) ? Documents::momentOf(Typed::string($document['since'])) : $startsAt,
            'invitesAGuest' => Typed::bool($document['invitesAGuest'] ?? false),
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
            'exchange' => Typed::object($state['exchange'], ExchangeId::class)->value,
            'accountId' => Typed::object($state['accountId'], AccountId::class)->value,
            'member' => Typed::object($state['member'], MemberId::class)->value,
            'provider' => Typed::object($state['provider'], Provider::class)->value,
            'contact' => Typed::objectOrNull($state['contact'], ContactId::class)?->value,
            'startsAt' => Documents::moment(Typed::object($state['startsAt'], \DateTimeImmutable::class)),
            'state' => Typed::object($state['state'], SyncState::class)->value,
            'reason' => Typed::stringOrNull($state['reason']),
            'since' => Documents::moment(Typed::object($state['since'], \DateTimeImmutable::class)),
            'invitesAGuest' => Typed::bool($state['invitesAGuest']),
        ];
    }
}
