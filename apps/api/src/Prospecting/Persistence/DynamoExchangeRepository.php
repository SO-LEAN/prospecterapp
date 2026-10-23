<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\Prospecting\Domain\ExchangeId;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * The exchanges in the table of Prospecting (ADR 0048, 0074, 0076):
 * - `EXCHANGE#<id>` / `EXCHANGE`: the exchange, listed in `GSI1` in the timeline of its contact
 *   (`TIMELINE#<account>#<person>`), under its position: the moment it happened and its id, the cursor of a slice;
 *   an exchange with the organization itself is in the timeline of no contact;
 * - `EXCHANGES#<organization>` / `EXCHANGE#<id>`: the exchanges kept for an organization, written with it, listed
 *   in `GSI1` in the timeline of the organization (`ORGANIZATION_TIMELINE#<organization>`) under the same position,
 *   and, while the exchange is to make conform, in `GSI2` among those of the organization
 *   (`TO_MAKE_CONFORM#<organization>`, ADR 0079), counted there.
 */
final readonly class DynamoExchangeRepository implements ExchangeRepository
{
    private const string EXCHANGE = 'EXCHANGE';

    public function __construct(private Table $prospectingTable)
    {
    }

    public function get(ExchangeId $id): Exchange
    {
        $item = $this->prospectingTable->item(self::keyOf($id), self::EXCHANGE) ?? throw UnknownExchange::identifiedBy($id);

        return ExchangeDocument::exchange($item['exchange']);
    }

    public function save(Exchange $exchange): void
    {
        $document = ExchangeDocument::of($exchange);
        $stored = $this->prospectingTable->item(self::keyOf($exchange->id()), self::EXCHANGE);

        $this->prospectingTable->transact([
            $this->prospectingTable->putting($this->itemOf($exchange, $document)),
            ...$this->linksToItsOrganizations($document, $exchange->isToMakeConform(), $stored['exchange']['keptFor'] ?? []),
        ]);
    }

    public function remove(Exchange $exchange): void
    {
        $document = ExchangeDocument::of($exchange);
        $stored = $this->prospectingTable->item(self::keyOf($exchange->id()), self::EXCHANGE);

        $this->prospectingTable->transact([
            $this->prospectingTable->deleting(self::keyOf($exchange->id()), self::EXCHANGE),
            ...$this->linksToItsOrganizations(['keptFor' => []] + $document, false, $stored['exchange']['keptFor'] ?? []),
        ]);
    }

    public function keptFor(OrganizationId $organization): array
    {
        $links = $this->prospectingTable->query(self::keptForKey($organization->value));

        return $this->exchangesOf($this->prospectingTable->items(array_map(static fn (array $link): array => [$link['SK'], self::EXCHANGE], $links)));
    }

    public function withContact(PersonId $contact, AccountId $accountId): array
    {
        return $this->exchangesOf($this->prospectingTable->query(self::timelineKey($accountId->value, $contact->value), Table::BY_GSI1));
    }

    public function timelineOf(PersonId $contact, AccountId $accountId, ?string $after, int $max): Slice
    {
        $found = $this->exchangesOf($this->prospectingTable->query(self::timelineKey($accountId->value, $contact->value), Table::BY_GSI1, $after, $max + 1));

        return Documents::slice($found, $max, self::positionOf(...));
    }

    public function timelineOfOrganization(OrganizationId $organization, ?string $after, int $max): Slice
    {
        $links = $this->prospectingTable->query(self::organizationTimelineKey($organization->value), Table::BY_GSI1, $after, $max + 1);

        return Documents::slice($this->exchangesOf($this->itemsListedBy($links)), $max, self::positionOf(...));
    }

    public function toMakeConformFor(OrganizationId $organization, ?string $after, int $max): Slice
    {
        $links = $this->prospectingTable->query(self::toMakeConformKey($organization->value), Table::BY_GSI2, $after, $max + 1);

        return Documents::slice($this->exchangesOf($this->itemsListedBy($links)), $max, self::positionOf(...));
    }

    public function countToMakeConformFor(OrganizationId $organization): int
    {
        return $this->prospectingTable->count(self::toMakeConformKey($organization->value), Table::BY_GSI2);
    }

    /**
     * The items of the exchanges the links name, in the order of the links.
     *
     * @param list<array<string, mixed>> $links
     *
     * @return list<array<string, mixed>>
     */
    private function itemsListedBy(array $links): array
    {
        $byKey = array_column($this->prospectingTable->items(array_map(static fn (array $link): array => [$link['SK'], self::EXCHANGE], $links)), null, 'PK');

        return array_values(array_filter(array_map(static fn (array $link): ?array => $byKey[$link['SK']] ?? null, $links)));
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    private function itemOf(Exchange $exchange, array $document): array
    {
        $inTheTimelineOfItsContact = null === $document['contact'] ? [] : [
            'GSI1PK' => self::timelineKey($document['accountId'], $document['contact']),
            'GSI1SK' => self::positionOf($exchange),
        ];

        return ['PK' => self::keyOf($exchange->id()), 'SK' => self::EXCHANGE, ...$inTheTimelineOfItsContact, 'exchange' => $document];
    }

    /**
     * The links to write and to delete so that the organizations the exchange is kept for are those of the
     * document, each at the moment of the exchange in the timeline of its organization: a correction may move it;
     * among those to make conform of its organization while it is.
     *
     * @param array<string, mixed> $document
     * @param list<string>         $storedKeptFor
     *
     * @return list<array<string, mixed>>
     */
    private function linksToItsOrganizations(array $document, bool $toMakeConform, array $storedKeptFor): array
    {
        $exchange = self::EXCHANGE.'#'.$document['id'];
        $position = $document['facts']['occurredAt'].'#'.$document['id'];
        $link = static fn (string $organization): array => [
            'PK' => self::keptForKey($organization),
            'SK' => $exchange,
            'GSI1PK' => self::organizationTimelineKey($organization),
            'GSI1SK' => $position,
            ...($toMakeConform ? ['GSI2PK' => self::toMakeConformKey($organization), 'GSI2SK' => $position] : []),
        ];

        return [
            ...array_map(fn (string $organization): array => $this->prospectingTable->putting($link($organization)), $document['keptFor']),
            ...array_map(fn (string $organization): array => $this->prospectingTable->deleting(self::keptForKey($organization), $exchange), array_values(array_diff($storedKeptFor, $document['keptFor']))),
        ];
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

    private static function positionOf(Exchange $exchange): string
    {
        return Documents::moment($exchange->facts()->occurredAt).'#'.$exchange->id();
    }

    private static function keyOf(ExchangeId $id): string
    {
        return self::EXCHANGE.'#'.$id->value;
    }

    private static function keptForKey(string $organization): string
    {
        return 'EXCHANGES#'.$organization;
    }

    private static function organizationTimelineKey(string $organization): string
    {
        return 'ORGANIZATION_TIMELINE#'.$organization;
    }

    private static function toMakeConformKey(string $organization): string
    {
        return 'TO_MAKE_CONFORM#'.$organization;
    }

    private static function timelineKey(string $accountId, string $contact): string
    {
        return sprintf('TIMELINE#%s#%s', $accountId, $contact);
    }
}
