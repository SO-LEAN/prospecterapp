<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Revision;
use App\Shared\Persistence\Table;
use App\Shared\Persistence\Write;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeNotedMeanwhile;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\CampaignId;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Erasure;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFingerprint;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Fingerprinted;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Stores the exchanges in Prospecting's table (ADR 0048, 0074, 0076):
 * - `EXCHANGE#<id>` / `EXCHANGE`: the exchange. `GSI1` lists it in its contact's timeline
 *   (`TIMELINE#<account>#<person>`) under its position. The position is the moment it happened and its id, and it is
 *   a slice's cursor. An exchange with the organization itself is in no contact's timeline.
 * - `EXCHANGES#<organization>` / `EXCHANGE#<id>`: an exchange kept for an organization, written with the exchange.
 *   `GSI1` lists it in the organization's timeline (`ORGANIZATION_TIMELINE#<organization>`) under the same position.
 *   While the exchange is to redact, `GSI2` also lists it among the organization's exchanges to redact
 *   (`TO_REDACT#<organization>`, ADR 0079), which are counted there.
 * - `AGENDA#<member>` / `EXCHANGE#<id>`: while the exchange is planned, it is in the agenda of the member who planned
 *   it. `GSI1` lists it (`AGENDA#<member>`) under the same position.
 * - `CALENDAR#<member>` / `EXCHANGE#<id>`: the exchange in its holder's calendar, whatever its status. The holder is
 *   the member who planned or logged it. `GSI1` lists it (`CALENDAR#<member>`) under the moment it happens, cancelled
 *   or not, then under its id.
 * `GSI2` lists the exchange among its account's exchanges (`ACCOUNT_EXCHANGES#<account>`, by its id). The exchange
 * carries its `revision`, which each save increments. It is the revision of its facts, which the aggregate does not
 * know. Its save and its removal also require the revision that their unit of work read (ADR 0181).
 */
final readonly class DynamoExchangeRepository implements ExchangeRepository
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function get(ExchangeId $id): Exchange
    {
        $item = $this->prospectingTable->item(ExchangeKeys::exchange($id->value), ExchangeKeys::EXCHANGE) ?? throw UnknownExchange::identifiedBy($id);

        return ExchangeDocument::exchange($item['exchange']);
    }

    public function save(Exchange $exchange): void
    {
        $document = ExchangeDocument::of($exchange);
        $stored = $this->prospectingTable->item(ExchangeKeys::exchange($exchange->id()->value), ExchangeKeys::EXCHANGE);

        $this->prospectingTable->transact([
            $this->prospectingTable->revising($this->itemOf($document, Revision::after($stored))),
            ...$this->linksToItsOrganizations($document, $exchange->isToMakeConform(), self::storedKeptFor($stored)),
            ...$this->linkInTheAgendaOfItsHolder($document, self::storedDocument($stored)),
            $this->prospectingTable->putting(self::linkInTheCalendarOfItsHolder($document)),
            ...$this->registrationOfItsFingerprint($exchange, $stored),
        ]);
    }

    /**
     * A new exchange registers its fingerprint in its account, on condition that no other exchange registered it
     * meanwhile. This condition is NotedOnce's safety net (ADR 0166). The registration outlives the exchange.
     *
     * @param array<string, mixed>|null $stored
     *
     * @return list<Write>
     */
    private function registrationOfItsFingerprint(Exchange $exchange, ?array $stored): array
    {
        $registration = self::registrationOf($exchange);
        if (null === $registration || null !== $stored) {
            return [];
        }

        return [$this->prospectingTable->putting($registration, 'attribute_not_exists(PK)', refusal: ExchangeNotedMeanwhile::byAnother(...))];
    }

    /**
     * `GSI1` lists the registration among its contact's fingerprints, so that it is forgotten with them.
     *
     * @return array{PK: string, SK: string, ...}|null
     */
    private static function registrationOf(Exchange $exchange): ?array
    {
        $fingerprint = $exchange->fingerprint();
        if (null === $fingerprint) {
            return null;
        }
        $registration = ['PK' => ExchangeKeys::fingerprint($exchange->accountId()->value, $fingerprint->value), 'SK' => ExchangeKeys::FINGERPRINT, 'exchangeId' => $exchange->id()->value];
        $contact = Typed::objectOrNull(Hydrator::stateOf($exchange)['contact'], ContactId::class);

        return null === $contact ? $registration : $registration + ['GSI1PK' => ExchangeKeys::fingerprintsOf($exchange->accountId()->value, $contact->value), 'GSI1SK' => $fingerprint->value];
    }

    public function remove(Exchange $exchange): void
    {
        $document = ExchangeDocument::of($exchange);
        $stored = $this->prospectingTable->item(ExchangeKeys::exchange($exchange->id()->value), ExchangeKeys::EXCHANGE);

        $this->prospectingTable->transact([
            $this->prospectingTable->deletingRevised(ExchangeKeys::exchange($exchange->id()->value), ExchangeKeys::EXCHANGE),
            ...$this->linksToItsOrganizations(['keptFor' => []] + $document, false, self::storedKeptFor($stored)),
            ...$this->linkInTheAgendaOfItsHolder(['status' => ExchangeStatus::Done->value] + $document, self::storedDocument($stored)),
            $this->prospectingTable->deleting(ExchangeKeys::calendar(Typed::string($document['createdBy'])), ExchangeKeys::exchange($exchange->id()->value)),
            ...$this->erasureOfItsFingerprint($exchange),
        ]);
    }

    /**
     * @param array<string, mixed>|null $stored the exchange's item as stored, null for a new exchange
     *
     * @return array<string, mixed>|null
     */
    private static function storedDocument(?array $stored): ?array
    {
        return null === $stored ? null : Typed::document($stored['exchange']);
    }

    /**
     * @param array<string, mixed>|null $stored the exchange's item as stored, null for a new exchange
     *
     * @return list<string>
     */
    private static function storedKeptFor(?array $stored): array
    {
        return Typed::strings(self::storedDocument($stored)['keptFor'] ?? []);
    }

    /**
     * An erased exchange's fingerprint keeps who erased it, so that nobody logs the exchange again (ADR 0166). The
     * registration is written whole, in case the exchange was logged before it registered its fingerprint. It is never
     * written over another exchange's registration.
     *
     * @return list<Write>
     */
    private function erasureOfItsFingerprint(Exchange $exchange): array
    {
        $registration = self::registrationOf($exchange);
        $erasure = Hydrator::stateOf($exchange)['erasure'];
        if (null === $registration || !$erasure instanceof Erasure) {
            return [];
        }

        return [$this->prospectingTable->putting(
            $registration + ['erasedBy' => $erasure->by->value, 'erasedAt' => Documents::moment($erasure->at)],
            'attribute_not_exists(PK) OR exchangeId = :exchange',
            [':exchange' => $exchange->id()->value],
        )];
    }

    public function fingerprinted(AccountId $accountId, ExchangeFingerprint $fingerprint): ?Fingerprinted
    {
        $item = $this->prospectingTable->item(ExchangeKeys::fingerprint($accountId->value, $fingerprint->value), ExchangeKeys::FINGERPRINT);

        if (null === $item) {
            return null;
        }
        $erasure = isset($item['erasedBy']) ? new Erasure(new MemberId(Typed::string($item['erasedBy'])), Documents::momentOf(Typed::string($item['erasedAt']))) : null;

        return new Fingerprinted(new ExchangeId(Typed::string($item['exchangeId'])), $erasure);
    }

    public function forgetFingerprintsOf(AccountId $accountId, ContactId $contact): void
    {
        $registrations = $this->prospectingTable->query(ExchangeKeys::fingerprintsOf($accountId->value, $contact->value), Table::BY_GSI1);
        $this->prospectingTable->transact(array_map(fn (array $registration): Write => $this->prospectingTable->deleting(Typed::string($registration['PK']), Typed::string($registration['SK'])), $registrations));
    }

    public function keptFor(OrganizationId $organization): array
    {
        $links = $this->prospectingTable->query(ExchangeKeys::keptFor($organization->value));

        return $this->exchangesOf($this->prospectingTable->items(array_map(static fn (string $exchange): array => [$exchange, ExchangeKeys::EXCHANGE], Typed::strings(array_column($links, 'SK')))));
    }

    public function withContact(ContactId $contact, AccountId $accountId): array
    {
        return $this->exchangesOf($this->prospectingTable->query(ExchangeKeys::timeline($accountId->value, $contact->value), Table::BY_GSI1));
    }

    /**
     * Reads each contact's timeline (GSI1). It reads the documents for their campaign only, and restores no exchange.
     *
     * @param list<ContactId> $contacts
     *
     * @return list<ContactId>
     */
    public function contactsWithAnExchangeOf(AccountId $accountId, CampaignId $campaign, array $contacts): array
    {
        return array_values(array_filter($contacts, fn (ContactId $contact): bool => array_any(
            $this->prospectingTable->query(ExchangeKeys::timeline($accountId->value, $contact->value), Table::BY_GSI1),
            static fn (array $item): bool => is_array($item['exchange']) && $campaign->value === ($item['exchange']['campaignId'] ?? null),
        )));
    }

    /**
     * Writes again every exchange's item with its revision kept, its link in its holder's calendar, and its
     * fingerprint's registration. A table written before the exchanges were listed by account, listed in calendars
     * or fingerprinted (ADR 0166) needs this once. A stored exchange's registration holds only its fingerprint, so
     * this writes over no erasure.
     */
    public function writeTheKeysAgain(): int
    {
        $items = $this->prospectingTable->scan(ExchangeKeys::EXCHANGE.'#');
        $this->prospectingTable->batch([
            ...array_map(fn (array $item): array => $this->itemOf(Typed::document($item['exchange']), Revision::of($item)), $items),
            ...array_map(static fn (array $item): array => self::linkInTheCalendarOfItsHolder(Typed::document($item['exchange'])), $items),
            ...array_values(array_filter(array_map(static fn (array $item): ?array => self::registrationOf(ExchangeDocument::exchange($item['exchange'])), $items))),
        ]);

        return count($items);
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array{PK: string, SK: string, ...}
     */
    private function itemOf(array $document, int $revision): array
    {
        $id = Typed::string($document['id']);
        $accountId = Typed::string($document['accountId']);
        $contact = Typed::stringOrNull($document['contact']);
        $inTheTimelineOfItsContact = null === $contact ? [] : [
            'GSI1PK' => ExchangeKeys::timeline($accountId, $contact),
            'GSI1SK' => ExchangeKeys::positionOf($document),
        ];

        return [
            'PK' => ExchangeKeys::exchange($id),
            'SK' => ExchangeKeys::EXCHANGE,
            ...$inTheTimelineOfItsContact,
            'GSI2PK' => ExchangeKeys::ofAccount($accountId),
            'GSI2SK' => $id,
            Revision::ATTRIBUTE => $revision,
            'exchange' => $document,
        ];
    }

    /**
     * Returns the links to write and to delete, so that the exchange is kept for the document's organizations. Each
     * link places the exchange at its moment in its organization's timeline, because a correction may move it. While
     * the exchange is to redact, the link also lists it among its organization's exchanges to redact.
     *
     * @param array<string, mixed> $document
     * @param list<string>         $storedKeptFor
     *
     * @return list<Write>
     */
    private function linksToItsOrganizations(array $document, bool $toMakeConform, array $storedKeptFor): array
    {
        $exchange = ExchangeKeys::exchange(Typed::string($document['id']));
        $position = ExchangeKeys::positionOf($document);
        $keptFor = Typed::strings($document['keptFor']);
        $link = static fn (string $organization): array => [
            'PK' => ExchangeKeys::keptFor($organization),
            'SK' => $exchange,
            'GSI1PK' => ExchangeKeys::organizationTimeline($organization),
            'GSI1SK' => $position,
            ...($toMakeConform ? ['GSI2PK' => ExchangeKeys::toRedact($organization), 'GSI2SK' => $position] : []),
        ];

        return [
            ...array_map(fn (string $organization): Write => $this->prospectingTable->putting($link($organization)), $keptFor),
            ...array_map(fn (string $organization): Write => $this->prospectingTable->deleting(ExchangeKeys::keptFor($organization), $exchange), array_values(array_diff($storedKeptFor, $keptFor))),
        ];
    }

    /**
     * While the exchange is planned, it is in the agenda of the member who planned it, at its moment. The link is
     * removed once the exchange is no longer planned.
     *
     * @param array<string, mixed>      $document
     * @param array<string, mixed>|null $stored
     *
     * @return list<Write>
     */
    private function linkInTheAgendaOfItsHolder(array $document, ?array $stored): array
    {
        $agenda = ExchangeKeys::agenda(Typed::string($document['createdBy']));
        $exchange = ExchangeKeys::exchange(Typed::string($document['id']));

        return match (true) {
            ExchangeStatus::Planned === ExchangeDocument::statusOf($document) => [$this->prospectingTable->putting(['PK' => $agenda, 'SK' => $exchange, 'GSI1PK' => $agenda, 'GSI1SK' => ExchangeKeys::positionOf($document)])],
            null !== $stored && ExchangeStatus::Planned === ExchangeDocument::statusOf($stored) => [$this->prospectingTable->deleting($agenda, $exchange)],
            default => [],
        };
    }

    /**
     * Whatever its status, the exchange is in the calendar of the member who planned or logged it, at the moment it
     * happens. A move or a correction moves it there too.
     *
     * @param array<string, mixed> $document
     *
     * @return array{PK: string, SK: string, GSI1PK: string, GSI1SK: string}
     */
    private static function linkInTheCalendarOfItsHolder(array $document): array
    {
        $calendar = ExchangeKeys::calendar(Typed::string($document['createdBy']));

        return ['PK' => $calendar, 'SK' => ExchangeKeys::exchange(Typed::string($document['id'])), 'GSI1PK' => $calendar, 'GSI1SK' => ExchangeKeys::calendarPositionOf($document)];
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return list<Exchange>
     */
    private function exchangesOf(array $items): array
    {
        return array_map(static fn (array $item): Exchange => ExchangeDocument::exchange($item['exchange']), $items);
    }
}
