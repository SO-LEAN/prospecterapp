<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Contract\Service\NameCampaigns;

/**
 * The service that Campaigns offers the other contexts (ADR 0045, 0089). It refuses nothing: what is unknown is absent.
 */
interface NameCampaignsService
{
    public function __invoke(NameCampaigns $query, NameCampaignsOutput $output): void;
}
