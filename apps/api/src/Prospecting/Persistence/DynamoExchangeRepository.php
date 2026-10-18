<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\Prospecting\Domain\ExchangeId;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * The exchanges in the table of Prospecting (ADR 0048, 0074):
 * - `EXCHANGE#<id>` / `EXCHANGE`: the exchange, listed in `GSI1` in the timeline of its contact
 *   (`TIMELINE#<account>#<person>`), under its position: the moment it happened and its id, the cursor of a slice;
 * - `EXCHANGES#<organization>` / `EXCHANGE#<id>`: the exchanges kept for an organization, written with it.
 */
final readonly class DynamoExchangeRepository implements ExchangeRepository
{
    private const string EXCHANGE = 'EXCHANGE';

    public function __construct(private Table $prospectingTable)
    {
    }

    public function save(Exchange $exchange): void
    {
        $document = ExchangeDocument::of($exchange);
        $stored = $this->prospectingTable->item(self::keyOf($exchange->id()), self::EXCHANGE);

        $this->prospectingTable->transact([
            $this->prospectingTable->putting($this->itemOf($exchange, $document)),
            ...$this->keptForChanged($document, $stored['exchange']['keptFor'] ?? []),
        ]);
    }

    public function remove(Exchange $exchange): void
    {
        $document = ExchangeDocument::of($exchange);
        $stored = $this->prospectingTable->item(self::keyOf($exchange->id()), self::EXCHANGE);

        $this->prospectingTable->transact([
            $this->prospectingTable->deleting(self::keyOf($exchange->id()), self::EXCHANGE),
            ...$this->keptForChanged(['keptFor' => []] + $document, $stored['exchange']['keptFor'] ?? []),
        ]);
    }

    public function keptFor(OrganizationId $organization): array
    {
        $links = $this->prospectingTable->query(self::keptForKey($organization->value));

        return $this->exchangesOf($this->prospectingTable->items(array_map(static fn (array $link): array => [$link['SK'], self::EXCHANGE], $links)));
    }

    public function timelineOf(PersonId $contact, AccountId $accountId, ?string $after, int $max): Slice
    {
        $found = $this->exchangesOf($this->prospectingTable->query(self::timelineKey($accountId->value, $contact->value), Table::BY_GSI1, $after, $max + 1));

        return Documents::slice($found, $max, self::positionOf(...));
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    private function itemOf(Exchange $exchange, array $document): array
    {
        return [
            'PK' => self::keyOf($exchange->id()),
            'SK' => self::EXCHANGE,
            'GSI1PK' => self::timelineKey($document['accountId'], $document['contact']),
            'GSI1SK' => self::positionOf($exchange),
            'exchange' => $document,
        ];
    }

    /**
     * The links to write and to delete so that the organizations the exchange is kept for are those of the
     * document.
     *
     * @param array<string, mixed> $document
     * @param list<string>         $storedKeptFor
     *
     * @return list<array<string, mixed>>
     */
    private function keptForChanged(array $document, array $storedKeptFor): array
    {
        $exchange = self::EXCHANGE.'#'.$document['id'];

        return [
            ...array_map(fn (string $organization): array => $this->prospectingTable->putting(['PK' => self::keptForKey($organization), 'SK' => $exchange]), array_values(array_diff($document['keptFor'], $storedKeptFor))),
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

    private static function timelineKey(string $accountId, string $contact): string
    {
        return sprintf('TIMELINE#%s#%s', $accountId, $contact);
    }
}
