<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchange;

use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultExchangeHandler implements QueryHandler
{
    public function __construct(
        private ExchangeRepository $exchanges,
        private Prospectors $prospectors,
    ) {
    }

    public function __invoke(ConsultExchange $query, ConsultExchangeOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));

        $output->present(ExchangeDetailResponse::of($this->exchanges->get(new ExchangeId($query->id)), $prospector));
    }
}
