<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichContactsHaveExchangesOfCampaign;

interface WhichContactsHaveExchangesOfCampaignOutput
{
    /**
     * @param list<string> $contactIds the contacts asked that have an exchange in the campaign
     */
    public function present(array $contactIds): void;
}
