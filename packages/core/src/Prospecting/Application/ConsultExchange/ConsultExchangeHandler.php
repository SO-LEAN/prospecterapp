<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchange;

use Prospecter\Core\Prospecting\Domain\ExchangeId;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultExchangeHandler implements QueryHandler
{
    public function __construct(
        private ExchangeRepository $exchanges,
        private ProspectorService $prospectorService,
    ) {
    }

    public function __invoke(ConsultExchange $query): ExchangeDetailResponse
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($query->prospectorId));

        return ExchangeDetailResponse::of($this->exchanges->get(new ExchangeId($query->id)), $prospector);
    }
}
