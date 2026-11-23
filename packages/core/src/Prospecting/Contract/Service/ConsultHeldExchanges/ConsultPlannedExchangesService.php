<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges;

/**
 * A service Prospecting offers the other contexts (ADR 0045, 0089, 0133). It gives the exchanges a holder still has
 * planned, soonest first, in the published language of `ConsultHeldExchanges`. It refuses nothing. A holder of another
 * account holds none there.
 */
interface ConsultPlannedExchangesService
{
    public function __invoke(ConsultPlannedExchanges $query, ConsultHeldExchangesOutput $output): void;
}
