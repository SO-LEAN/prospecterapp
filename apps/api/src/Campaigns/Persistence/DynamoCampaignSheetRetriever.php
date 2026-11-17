<?php

namespace App\Campaigns\Persistence;

use App\Shared\Persistence\Table;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\Port\CampaignSheetRetriever;
use Prospecter\Core\Campaigns\Domain\Exception\UnknownCampaign;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;

/**
 * The campaigns read from the items of `DynamoCampaignRepository`, without restoring them (ADR 0088): one, or every
 * campaign of the account at once, the range `CAMPAIGN#` of its partition (ADR 0144).
 */
final readonly class DynamoCampaignSheetRetriever implements CampaignSheetRetriever
{
    public function __construct(private Table $campaignsTable)
    {
    }

    public function get(AccountId $accountId, CampaignId $id): CampaignSheet
    {
        $document = $this->campaignsTable->item(CampaignsKeys::account($accountId->value), CampaignsKeys::campaign($id->value))['campaign'] ?? throw UnknownCampaign::identifiedBy($id);

        return CampaignDocument::sheet($document);
    }

    public function ofAccount(AccountId $accountId): array
    {
        $items = $this->campaignsTable->queryBetween(CampaignsKeys::account($accountId->value), null, ...CampaignsKeys::campaigns());

        return array_reverse(array_map(static fn (array $item): CampaignSheet => CampaignDocument::sheet($item['campaign']), $items));
    }
}
