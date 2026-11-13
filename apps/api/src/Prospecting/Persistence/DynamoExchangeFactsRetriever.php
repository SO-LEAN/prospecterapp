<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Revision;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\Port\ExchangeFactsRetriever;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ExchangeFactsResponse;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Reads the facts of the exchanges in the items `EXCHANGE#<id>` / `EXCHANGE` that `DynamoExchangeRepository`
 * writes, their revision with them, without restoring them (ADR 0088): by key, as the open unit of work is about
 * to write them, and listed by account in `GSI2` (`ExchangeKeys::ofAccount()`).
 */
final readonly class DynamoExchangeFactsRetriever implements ExchangeFactsRetriever
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function of(ExchangeId $id): ExchangeFactsResponse
    {
        $item = $this->prospectingTable->item(ExchangeKeys::exchange($id->value), ExchangeKeys::EXCHANGE);

        return null === $item ? ExchangeFactsResponse::absent($id->value) : self::factsOf($item);
    }

    public function sliceOf(AccountId $accountId, ?string $after, int $max): Slice
    {
        $items = $this->prospectingTable->query(ExchangeKeys::ofAccount($accountId->value), Table::BY_GSI2, $after, $max + 1);

        return Documents::slice(array_map(self::factsOf(...), $items), $max, static fn (ExchangeFactsResponse $facts): string => $facts->exchangeId);
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function factsOf(array $item): ExchangeFactsResponse
    {
        return new ExchangeFactsResponse($item['exchange']['id'], Revision::of($item), true, ExchangeDocument::statusOf($item['exchange'])->value, Documents::momentOf($item['exchange']['facts']['occurredAt']));
    }
}
