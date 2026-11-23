<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultPlannedExchanges;

use Prospecter\Core\Prospecting\Application\Port\HeldExchangeRetriever;
use Prospecter\Core\Prospecting\Application\PublishedHeldExchanges;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultHeldExchangesOutput;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultPlannedExchanges;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultPlannedExchangesService;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Reads the exchanges a member still has planned from a given moment, the soonest first. Prospecter pushes them into
 * the member's calendar (ADR 0133).
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
