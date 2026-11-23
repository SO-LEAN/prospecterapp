<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Repository;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownCampaign;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

interface CampaignRepository
{
    /**
     * @throws UnknownCampaign when the account has no campaign with this id
     */
    public function get(AccountId $accountId, CampaignId $id): Campaign;

    /** Null when the account has no campaign with this id. */
    public function find(AccountId $accountId, CampaignId $id): ?Campaign;

    /**
     * @return list<Campaign> the account's campaigns that keep this contact among their recipients
     */
    public function withRecipient(AccountId $accountId, ContactId $contact): array;

    public function save(Campaign $campaign): void;

    public function remove(Campaign $campaign): void;
}
