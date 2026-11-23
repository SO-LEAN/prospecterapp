<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultCampaign;

use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\CampaignTargets;
use Prospecter\Core\Campaigns\Application\Port\CampaignSheetRetriever;
use Prospecter\Core\Campaigns\Application\RecipientInSheet;
use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Policy\CampaignProgress;
use Prospecter\Core\Campaigns\Domain\Port\Campaigners;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultCampaignHandler implements QueryHandler
{
    public function __construct(
        private CampaignSheetRetriever $campaignSheetRetriever,
        private Campaigners $campaigners,
        private CampaignTargets $campaignTargets,
    ) {
    }

    public function __invoke(ConsultCampaign $query, ConsultCampaignOutput $output): void
    {
        $accountId = $this->campaigners->accountOf(new MemberId($query->memberId));
        $campaign = $this->campaignSheetRetriever->get($accountId, new CampaignId($query->campaignId));

        $output->present(
            $campaign,
            $this->campaignTargets->counted($accountId, [$campaign])[$campaign->id],
            CampaignProgress::processedOnTheDayOf($query->at, new \DateTimeZone($query->timeZone), self::processingMomentsOf($campaign)),
        );
    }

    /**
     * @return list<?\DateTimeImmutable>
     */
    private static function processingMomentsOf(CampaignSheet $campaign): array
    {
        return array_values(array_map(static fn (RecipientInSheet $recipient): ?\DateTimeImmutable => $recipient->processedAt, $campaign->recipients));
    }
}
