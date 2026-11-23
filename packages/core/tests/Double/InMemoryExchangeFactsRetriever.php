<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\Port\ExchangeFactsRetriever;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ExchangeFactsResponse;
use Prospecter\Core\Prospecting\Domain\Model\CampaignId;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Search\SearchKeys;
use Prospecter\Core\SharedKernel\Domain\Search\Slice;

/**
 * Reads the facts of the exchanges that the in-memory repository saved, with their revision. The adapter reads
 * the table's items in the same way.
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

        return new ExchangeFactsResponse((string) $exchange->id(), $this->database->revisionOf((string) $exchange->id()), true, $state->object('status', ExchangeStatus::class)->value, $state->object('facts', ExchangeFacts::class)->occurredAt, $state->objectOrNull('contact', ContactId::class)?->value, $state->objectOrNull('campaign', CampaignId::class)?->value);
    }
}
