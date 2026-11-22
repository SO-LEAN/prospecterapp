<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichContactsHaveExchangesOfCampaign;

interface WhichContactsHaveExchangesOfCampaignOutput
{
    /**
     * @param list<string> $contactIds those, among the contacts asked, with an exchange of the campaign
     */
    public function present(array $contactIds): void;
}
