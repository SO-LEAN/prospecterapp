<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges;

/**
 * The service Prospecting offers the other contexts (ADR 0045, 0089): it refuses nothing, an exchange of another
 * account, erased, or kept for no organization any more is absent from its answer.
 */
interface ConsultHeldExchangesService
{
    public function __invoke(ConsultHeldExchanges $query, ConsultHeldExchangesOutput $output): void;
}
