<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\ConsultHeldExchanges\HeldExchange;
use Prospecter\Core\Prospecting\Application\Port\HeldExchangeRetriever;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the items `EXCHANGE#<id>` / `EXCHANGE` that `DynamoExchangeRepository` writes as their holders hold them, in
 * one batch, without restoring them (ADR 0088, 0127); those a holder still has planned by the links of their agenda
 * (`AGENDA#<member>` in `GSI1`, ADR 0133).
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

    public function plannedBy(AccountId $accountId, MemberId $holder, \DateTimeImmutable $from): array
    {
        $links = $this->prospectingTable->queryAfter(ExchangeKeys::agenda($holder->value), Table::BY_GSI1, ExchangeKeys::positionBefore($from));
        $items = array_column($this->prospectingTable->items(array_map(static fn (array $link): array => [$link['SK'], ExchangeKeys::EXCHANGE], $links)), null, 'PK');
        $planned = array_filter(
            array_map(static fn (array $link): ?array => $items[$link['SK']]['exchange'] ?? null, $links),
            static fn (?array $document): bool => null !== $document && $accountId->value === $document['accountId'],
        );

        return array_values(array_map(self::heldExchangeOf(...), $planned));
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
            ExchangeDocument::linkRequestedOf($document),
            $document['contact'],
            $document['keptFor'],
        );
    }
}
