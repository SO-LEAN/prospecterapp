<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichContactsHaveExchangesOfCampaign;

/**
 * A service Prospecting offers the other contexts (ADR 0045, 0195). It refuses nothing. Its answer leaves out what
 * is unknown.
 */
interface WhichContactsHaveExchangesOfCampaignService
{
    public function __invoke(WhichContactsHaveExchangesOfCampaign $query, WhichContactsHaveExchangesOfCampaignOutput $output): void;
}
