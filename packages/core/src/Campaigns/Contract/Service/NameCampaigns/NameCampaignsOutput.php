<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Contract\Service\NameCampaigns;

interface NameCampaignsOutput
{
    /**
     * @param array<string, string> $names the name of each requested campaign that the account has, by id; an unknown one is absent
     */
    public function present(array $names): void;
}
