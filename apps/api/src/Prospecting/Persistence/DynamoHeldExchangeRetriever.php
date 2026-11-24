<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultHeldExchanges\HeldExchange;
use Prospecter\Core\Prospecting\Application\Port\HeldExchangeRetriever;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Reads the items `EXCHANGE#<id>` / `EXCHANGE` that `DynamoExchangeRepository` writes, as their holders hold them. It
 * reads them in one batch and does not restore them (ADR 0088, 0127). It finds the exchanges a holder still has
 * planned through the links in their agenda (`AGENDA#<member>` in `GSI1`, ADR 0133).
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

        return self::heldOf($accountId, array_map(static fn (array $item): array => Typed::document($item['exchange']), $items));
    }

    public function plannedBy(AccountId $accountId, MemberId $holder, \DateTimeImmutable $from): array
    {
        $links = $this->prospectingTable->queryAfter(ExchangeKeys::agenda($holder->value), Table::BY_GSI1, ExchangeKeys::positionBefore($from));
        $items = $this->prospectingTable->itemsInTheOrderOf(Typed::strings(array_column($links, 'SK')), ExchangeKeys::EXCHANGE);

        return self::heldOf($accountId, array_map(static fn (array $item): array => Typed::document($item['exchange']), $items));
    }

    /**
     * @param list<array<string, mixed>> $documents
     *
     * @return list<HeldExchange> the account's exchanges
     */
    private static function heldOf(AccountId $accountId, array $documents): array
    {
        $ofTheAccount = array_filter($documents, static fn (array $document): bool => $accountId->value === $document['accountId']);

        return array_values(array_map(self::heldExchangeOf(...), $ofTheAccount));
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function heldExchangeOf(array $document): HeldExchange
    {
        $facts = Typed::document($document['facts']);

        return new HeldExchange(
            Typed::string($document['id']),
            Typed::string($document['createdBy']),
            ExchangeDocument::statusOf($document)->value,
            Typed::string($facts['channel']),
            Documents::momentOf(Typed::string($facts['occurredAt'])),
            ExchangeDocument::durationInMinutesOf($facts),
            Typed::stringOrNull($facts['place'] ?? null),
            Typed::stringOrNull($facts['link'] ?? null),
            ExchangeDocument::linkRequestedOf($document),
            Typed::stringOrNull($document['contact']),
            Typed::strings($document['keptFor']),
            Typed::bool($document['bookedByLink'] ?? false),
        );
    }
}
