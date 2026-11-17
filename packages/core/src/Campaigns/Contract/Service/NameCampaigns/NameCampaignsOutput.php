<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Contract\Service\NameCampaigns;

interface NameCampaignsOutput
{
    /**
     * @param array<string, string> $names the name of each campaign asked the account has, by identity; an unknown one is absent
     */
    public function present(array $names): void;
}
