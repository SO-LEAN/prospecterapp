<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Contract\Service\NameCampaigns;

/** Keeps the answer as is, for a client that reads it rather than shows it (ADR 0085). */
final class KeptCampaignNames implements NameCampaignsOutput
{
    /**
     * @var array<string, string>
     */
    public private(set) array $names = [];

    public function present(array $names): void
    {
        $this->names = $names;
    }
}
