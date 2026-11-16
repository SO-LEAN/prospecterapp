<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultPlannedExchanges;

use Prospecter\Core\Prospecting\Application\Port\HeldExchangeRetriever;
use Prospecter\Core\Prospecting\Application\PublishedHeldExchanges;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultHeldExchangesOutput;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultPlannedExchanges;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultPlannedExchangesService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The exchanges a member still has planned from a moment on, the soonest first, for the calendar they are pushed into
 * (ADR 0133).
 */
final readonly class ConsultPlannedExchangesHandler implements QueryHandler, ConsultPlannedExchangesService
{
    public function __construct(
        private HeldExchangeRetriever $heldExchangeRetriever,
        private PublishedHeldExchanges $publishedHeldExchanges,
    ) {
    }

    public function __invoke(ConsultPlannedExchanges $query, ConsultHeldExchangesOutput $output): void
    {
        $planned = $this->heldExchangeRetriever->plannedBy(new AccountId($query->accountId), new MemberId($query->holderId), $query->from);

        $output->present($this->publishedHeldExchanges->of($planned));
    }
}
