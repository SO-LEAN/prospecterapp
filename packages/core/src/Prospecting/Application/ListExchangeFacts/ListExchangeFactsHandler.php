<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ListExchangeFacts;

use Prospecter\Core\Prospecting\Application\Port\ExchangeFactsRetriever;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ListExchangeFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ListExchangeFactsOutput;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ListExchangeFactsService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;

final readonly class ListExchangeFactsHandler implements QueryHandler, ListExchangeFactsService
{
    public function __construct(private ExchangeFactsRetriever $exchangeFactsRetriever)
    {
    }

    public function __invoke(ListExchangeFacts $query, ListExchangeFactsOutput $output): void
    {
        $slice = $this->exchangeFactsRetriever->sliceOf(new AccountId($query->accountId), $query->after, $query->max);

        $output->present($slice->content, $slice->next);
    }
}
