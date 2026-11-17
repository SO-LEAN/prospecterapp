<?php

namespace App\Campaigns\Persistence;

use App\Shared\Persistence\Table;
use Prospecter\Core\Campaigns\Domain\Exception\UnknownCampaign;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The campaigns of an account in its partition of the table of Campaigns (ADR 0144).
 */
final readonly class DynamoCampaignRepository implements CampaignRepository
{
    public function __construct(private Table $campaignsTable)
    {
    }

    public function get(AccountId $accountId, CampaignId $id): Campaign
    {
        $document = $this->campaignsTable->item(CampaignsKeys::account($accountId->value), CampaignsKeys::campaign($id->value))['campaign'] ?? throw UnknownCampaign::identifiedBy($id);

        return CampaignDocument::campaign($document);
    }

    public function save(Campaign $campaign): void
    {
        $this->campaignsTable->transact([$this->campaignsTable->putting([
            'PK' => CampaignsKeys::account($campaign->accountId()->value),
            'SK' => CampaignsKeys::campaign($campaign->id()->value),
            'campaign' => CampaignDocument::of($campaign),
        ])]);
    }

    public function remove(Campaign $campaign): void
    {
        $this->campaignsTable->transact([$this->campaignsTable->deleting(CampaignsKeys::account($campaign->accountId()->value), CampaignsKeys::campaign($campaign->id()->value))]);
    }
}
