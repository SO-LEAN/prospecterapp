<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Port;

use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Domain\Exception\UnknownCampaign;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;

/**
 * Reads the campaigns of an account as their repository saved them, without restoring them (ADR 0088), asking the
 * domain their targeted tags and the intents their state offers.
 */
interface CampaignSheetRetriever
{
    /**
     * @throws UnknownCampaign when the account has no campaign of this identity
     */
    public function get(AccountId $accountId, CampaignId $id): CampaignSheet;

    /**
     * None when the account has no campaign of this identity.
     */
    public function find(AccountId $accountId, CampaignId $id): ?CampaignSheet;

    /**
     * @param list<CampaignId> $ids
     *
     * @return array<string, string> the name of each campaign the account has among them, by identity
     */
    public function namesOf(AccountId $accountId, array $ids): array;

    /**
     * @return list<CampaignSheet> every campaign of the account, the newest first (ADR 0144)
     */
    public function ofAccount(AccountId $accountId): array;
}
