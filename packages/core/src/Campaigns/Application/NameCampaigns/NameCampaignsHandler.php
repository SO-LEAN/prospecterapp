<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\NameCampaigns;

use Prospecter\Core\Campaigns\Application\Port\CampaignSheetRetriever;
use Prospecter\Core\Campaigns\Contract\Service\NameCampaigns\NameCampaigns;
use Prospecter\Core\Campaigns\Contract\Service\NameCampaigns\NameCampaignsOutput;
use Prospecter\Core\Campaigns\Contract\Service\NameCampaigns\NameCampaignsService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;

final readonly class NameCampaignsHandler implements QueryHandler, NameCampaignsService
{
    public function __construct(private CampaignSheetRetriever $campaignSheetRetriever)
    {
    }

    public function __invoke(NameCampaigns $query, NameCampaignsOutput $output): void
    {
        $output->present($this->campaignSheetRetriever->namesOf(
            new AccountId($query->accountId),
            array_map(static fn (string $id): CampaignId => new CampaignId($id), array_values(array_unique($query->campaignIds))),
        ));
    }
}
