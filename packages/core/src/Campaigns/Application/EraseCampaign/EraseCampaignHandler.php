<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\EraseCampaign;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownCampaign;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Port\TaggedAccounts;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\CampaignId;

/**
 * The operations erase a campaign whatever its status, found by its id alone. They do so to clean up a test, or on a
 * person's request under the GDPR. No member does this, because the application never deletes a started campaign.
 */
final readonly class EraseCampaignHandler implements CommandHandler
{
    public function __construct(
        private CampaignRepository $campaigns,
        private TaggedAccounts $accounts,
    ) {
    }

    /**
     * @throws UnknownCampaign
     */
    public function __invoke(EraseCampaign $command): void
    {
        $this->campaigns->remove($this->found(new CampaignId($command->campaignId)));
    }

    /**
     * @throws UnknownCampaign when no account has it
     */
    private function found(CampaignId $id): Campaign
    {
        foreach ($this->accounts->all() as $accountId) {
            $campaign = $this->campaigns->find($accountId, $id);

            if (null !== $campaign) {
                return $campaign;
            }
        }

        throw UnknownCampaign::identifiedBy($id);
    }
}
