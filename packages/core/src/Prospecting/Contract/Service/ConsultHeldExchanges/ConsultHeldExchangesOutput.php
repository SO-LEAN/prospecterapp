<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges;

interface ConsultHeldExchangesOutput
{
    /**
     * @param list<HeldExchangeResponse> $exchanges the account's exchanges among those asked, none of them erased
     */
    public function present(array $exchanges): void;
}
