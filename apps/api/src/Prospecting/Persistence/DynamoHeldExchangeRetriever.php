<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\ConsultHeldExchanges\HeldExchange;
use Prospecter\Core\Prospecting\Application\Port\HeldExchangeRetriever;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Reads the items `EXCHANGE#<id>` / `EXCHANGE` that `DynamoExchangeRepository` writes as their holders hold them, in
 * one batch, without restoring them (ADR 0088, 0127).
 */
final readonly class DynamoHeldExchangeRetriever implements HeldExchangeRetriever
{
    public function __construct(private Table $prospectingTable)
    {
    }

    /**
     * @param list<ExchangeId> $ids
     */
    public function ofIds(AccountId $accountId, array $ids): array
    {
        $items = $this->prospectingTable->items(array_map(static fn (ExchangeId $id): array => [ExchangeKeys::exchange($id->value), ExchangeKeys::EXCHANGE], $ids));
        $ofTheAccount = array_filter($items, static fn (array $item): bool => $accountId->value === $item['exchange']['accountId']);

        return array_values(array_map(static fn (array $item): HeldExchange => self::heldExchangeOf($item['exchange']), $ofTheAccount));
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function heldExchangeOf(array $document): HeldExchange
    {
        $facts = $document['facts'];

        return new HeldExchange(
            $document['id'],
            $document['createdBy'],
            ExchangeDocument::statusOf($document)->value,
            $facts['channel'],
            Documents::momentOf($facts['occurredAt']),
            isset($facts['durationInMinutes']) ? (int) $facts['durationInMinutes'] : null,
            $facts['place'] ?? null,
            $facts['link'] ?? null,
            $document['contact'],
            $document['keptFor'],
        );
    }
}
