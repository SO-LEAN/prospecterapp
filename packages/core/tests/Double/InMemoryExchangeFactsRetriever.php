<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\Port\ExchangeFactsRetriever;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ExchangeFactsResponse;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\SearchKeys;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Reads the facts of the exchanges the in-memory repository saved, as the adapter reads the items of the table,
 * their revision with them.
 */
final readonly class InMemoryExchangeFactsRetriever implements ExchangeFactsRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function of(ExchangeId $id): ExchangeFactsResponse
    {
        $exchange = $this->database->exchange((string) $id);

        return null === $exchange ? ExchangeFactsResponse::absent((string) $id) : $this->factsOf($exchange);
    }

    public function sliceOf(AccountId $accountId, ?string $after, int $max): Slice
    {
        $kept = array_values(array_filter($this->database->exchanges(), static fn (Exchange $exchange): bool => $exchange->accountId()->equals($accountId)));
        $slice = NewestFirst::slice($kept, static fn (Exchange $exchange): string => (string) $exchange->id(), static fn (): SearchKeys => new SearchKeys(null, null), null, $after, $max);

        return new Slice(array_map($this->factsOf(...), $slice->content), $slice->next);
    }

    private function factsOf(Exchange $exchange): ExchangeFactsResponse
    {
        $state = StateOf::the($exchange);

        return new ExchangeFactsResponse((string) $exchange->id(), $this->database->revisionOf((string) $exchange->id()), true, $state['status']->value, $state['facts']->occurredAt);
    }
}
