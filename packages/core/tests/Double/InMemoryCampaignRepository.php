<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownCampaign;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The DynamoDB repository in the scenarios: the campaigns kept in the partition of their account.
 */
final readonly class InMemoryCampaignRepository implements CampaignRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(AccountId $accountId, CampaignId $id): Campaign
    {
        return $this->database->campaign($accountId->value, $id->value) ?? throw UnknownCampaign::identifiedBy($id);
    }

    public function save(Campaign $campaign): void
    {
        $this->database->storeCampaign($campaign);
    }

    public function remove(Campaign $campaign): void
    {
        $this->database->forgetCampaign($campaign);
    }
}
