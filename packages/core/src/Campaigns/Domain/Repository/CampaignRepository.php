<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Repository;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownCampaign;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\SharedKernel\Domain\AccountId;

interface CampaignRepository
{
    /**
     * @throws UnknownCampaign when the account has no campaign of this identity
     */
    public function get(AccountId $accountId, CampaignId $id): Campaign;

    public function save(Campaign $campaign): void;

    public function remove(Campaign $campaign): void;
}
