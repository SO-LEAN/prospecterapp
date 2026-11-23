<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges;

/**
 * A service Prospecting offers the other contexts (ADR 0045, 0089). It refuses nothing. Its answer leaves out an
 * exchange of another account, an erased exchange, and an exchange no longer kept for any organization.
 */
interface ConsultHeldExchangesService
{
    public function __invoke(ConsultHeldExchanges $query, ConsultHeldExchangesOutput $output): void;
}
