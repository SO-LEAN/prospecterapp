<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges;

/**
 * The service Prospecting offers the other contexts (ADR 0045, 0089, 0133): the exchanges a holder still has planned,
 * the soonest first, in the published language of `ConsultHeldExchanges`; it refuses nothing, a holder of another
 * account holding none there.
 */
interface ConsultPlannedExchangesService
{
    public function __invoke(ConsultPlannedExchanges $query, ConsultHeldExchangesOutput $output): void;
}
