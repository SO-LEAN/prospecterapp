<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges;

interface ConsultHeldExchangesOutput
{
    /**
     * @param list<HeldExchangeResponse> $exchanges those of the account among those asked, none erased
     */
    public function present(array $exchanges): void;
}
