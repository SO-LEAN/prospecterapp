<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultHeldExchanges;

use Prospecter\Core\Prospecting\Application\Port\HeldExchangeRetriever;
use Prospecter\Core\Prospecting\Application\PublishedHeldExchanges;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultHeldExchanges;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultHeldExchangesOutput;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultHeldExchangesService;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The exchanges among these ids, a client asking about one at a time.
 */
final readonly class ConsultHeldExchangesHandler implements QueryHandler, ConsultHeldExchangesService
{
    public function __construct(
        private HeldExchangeRetriever $heldExchangeRetriever,
        private PublishedHeldExchanges $publishedHeldExchanges,
    ) {
    }

    public function __invoke(ConsultHeldExchanges $query, ConsultHeldExchangesOutput $output): void
    {
        $held = $this->heldExchangeRetriever->ofIds(new AccountId($query->accountId), array_map(static fn (string $id): ExchangeId => new ExchangeId($id), $query->exchangeIds));

        $output->present($this->publishedHeldExchanges->of($held));
    }
}
