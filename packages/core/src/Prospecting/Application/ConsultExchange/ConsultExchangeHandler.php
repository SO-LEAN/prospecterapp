<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchange;

use Prospecter\Core\Prospecting\Application\Port\ExchangeDetailRetriever;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultExchangeHandler implements QueryHandler
{
    public function __construct(
        private ExchangeDetailRetriever $exchangeDetailRetriever,
        private Prospectors $prospectors,
    ) {
    }

    public function __invoke(ConsultExchange $query, ConsultExchangeOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));

        $output->present($this->exchangeDetailRetriever->get(new ExchangeId($query->id), $prospector));
    }
}
