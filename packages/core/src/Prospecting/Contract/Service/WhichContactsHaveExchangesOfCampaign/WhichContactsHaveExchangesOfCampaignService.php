<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichContactsHaveExchangesOfCampaign;

/**
 * The service Prospecting offers the other contexts (ADR 0045, 0195): it refuses nothing, what is unknown is absent.
 */
interface WhichContactsHaveExchangesOfCampaignService
{
    public function __invoke(WhichContactsHaveExchangesOfCampaign $query, WhichContactsHaveExchangesOfCampaignOutput $output): void;
}
