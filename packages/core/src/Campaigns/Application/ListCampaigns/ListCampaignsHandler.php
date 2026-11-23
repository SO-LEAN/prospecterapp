<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ListCampaigns;

use Prospecter\Core\Campaigns\Application\CampaignTargets;
use Prospecter\Core\Campaigns\Application\Port\CampaignSheetRetriever;
use Prospecter\Core\Campaigns\Domain\Port\Campaigners;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class ListCampaignsHandler implements QueryHandler
{
    public function __construct(
        private CampaignSheetRetriever $campaignSheetRetriever,
        private Campaigners $campaigners,
        private CampaignTargets $campaignTargets,
    ) {
    }

    public function __invoke(ListCampaigns $query, ListCampaignsOutput $output): void
    {
        $accountId = $this->campaigners->accountOf(new MemberId($query->memberId));
        $campaigns = $this->campaignSheetRetriever->ofAccount($accountId);

        $output->present($campaigns, $this->campaignTargets->counted($accountId, $campaigns));
    }
}
