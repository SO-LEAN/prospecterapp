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
use Prospecter\Core\Prospecting\Domain\Model\Erasure;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFingerprint;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Fingerprinted;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The exchanges in the table of Prospecting (ADR 0048, 0074, 0076):
 * - `EXCHANGE#<id>` / `EXCHANGE`: the exchange, listed in `GSI1` in the timeline of its contact
 *   (`TIMELINE#<account>#<person>`), under its position: the moment it happened and its id, the cursor of a slice;
 *   an exchange with the organization itself is in the timeline of no contact;
 * - `EXCHANGES#<organization>` / `EXCHANGE#<id>`: the exchanges kept for an organization, written with it, listed
 *   in `GSI1` in the timeline of the organization (`ORGANIZATION_TIMELINE#<organization>`) under the same position,
 *   and, while the exchange is to be redacted, in `GSI2` among those of the organization
 *   (`TO_REDACT#<organization>`, ADR 0079), counted there;
 * - `AGENDA#<member>` / `EXCHANGE#<id>`: while it is planned, the exchange in the agenda of the member who planned
 *   it, listed in `GSI1` (`AGENDA#<member>`) under the same position;
 * - `CALENDAR#<member>` / `EXCHANGE#<id>`: the exchange in the calendar of its holder, who planned or noted it,
 *   whatever its status, listed in `GSI1` (`CALENDAR#<member>`) under the moment it happens, cancelled or not, then
 *   its id.
 * The exchange is listed in `GSI2` among those of its account (`ACCOUNT_EXCHANGES#<account>`, its id), and
 * carries its `revision`, incremented at each save: the revision of its facts, which the aggregate does not know,
 * and the one its save and its removal hold to, as their unit of work read it (ADR 0181).
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
     * A new exchange registers its fingerprint in its account, under the condition that no other exchange did
     * meanwhile: the safety net of NotedOnce (ADR 0166). The registration outlives the exchange.
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
     * Listed among the fingerprints of its contact in `GSI1`, to be forgotten with them.
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
        $contact = Typed::objectOrNull(Hydrator::stateOf($exchange)['contact'], PersonId::class);

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
     * @param array<string, mixed>|null $stored the item of the exchange as stored, none for a new one
     *
     * @return array<string, mixed>|null
     */
    private static function storedDocument(?array $stored): ?array
    {
        return null === $stored ? null : Typed::document($stored['exchange']);
    }

    /**
     * @param array<string, mixed>|null $stored the item of the exchange as stored, none for a new one
     *
     * @return list<string>
     */
    private static function storedKeptFor(?array $stored): array
    {
        return Typed::strings(self::storedDocument($stored)['keptFor'] ?? []);
    }

    /**
     * The fingerprint of an erased exchange keeps who erased it, so that it is not noted again (ADR 0166); written
     * whole, in case the exchange was noted before it registered it, and never over that of another exchange.
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

    public function forgetFingerprintsOf(AccountId $accountId, PersonId $contact): void
    {
        $registrations = $this->prospectingTable->query(ExchangeKeys::fingerprintsOf($accountId->value, $contact->value), Table::BY_GSI1);
        $this->prospectingTable->transact(array_map(fn (array $registration): Write => $this->prospectingTable->deleting(Typed::string($registration['PK']), Typed::string($registration['SK'])), $registrations));
    }

    public function keptFor(OrganizationId $organization): array
    {
        $links = $this->prospectingTable->query(ExchangeKeys::keptFor($organization->value));

        return $this->exchangesOf($this->prospectingTable->items(array_map(static fn (string $exchange): array => [$exchange, ExchangeKeys::EXCHANGE], Typed::strings(array_column($links, 'SK')))));
    }

    public function withContact(PersonId $contact, AccountId $accountId): array
    {
        return $this->exchangesOf($this->prospectingTable->query(ExchangeKeys::timeline($accountId->value, $contact->value), Table::BY_GSI1));
    }

    /**
     * Each contact's timeline (GSI1), its documents read for their campaign only: no exchange is restored.
     *
     * @param list<PersonId> $contacts
     *
     * @return list<PersonId>
     */
    public function contactsWithAnExchangeOf(AccountId $accountId, CampaignId $campaign, array $contacts): array
    {
        return array_values(array_filter($contacts, fn (PersonId $contact): bool => array_any(
            $this->prospectingTable->query(ExchangeKeys::timeline($accountId->value, $contact->value), Table::BY_GSI1),
            static fn (array $item): bool => is_array($item['exchange']) && $campaign->value === ($item['exchange']['campaignId'] ?? null),
        )));
    }

    /**
     * Writes again the item of every exchange, its revision kept, its link in the calendar of its holder, and the
     * registration of its fingerprint: what a table written before the exchanges were listed by account, in the
     * calendars, or fingerprinted (ADR 0166), needs once. A stored exchange holds its fingerprint alone: no erasure is
     * written over.
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
     * The links to write and to delete so that the organizations the exchange is kept for are those of the
     * document, each at the moment of the exchange in the timeline of its organization: a correction may move it;
     * among those to redact of its organization while it is.
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
     * While it is planned, the exchange is in the agenda of the member who planned it, at its moment; the link
     * removed once it no longer is.
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
     * Whatever its status, the exchange is in the calendar of the member who planned or noted it, at the moment it
     * happens: a move or a correction moves it there.
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
