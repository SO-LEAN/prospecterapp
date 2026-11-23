<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Port;

use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Domain\Exception\UnknownCampaign;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;

/**
 * Reads an account's campaigns as their repository saved them, without restoring them (ADR 0088). It asks the domain
 * for their targeted tags and for the intents their state allows.
 */
interface CampaignSheetRetriever
{
    /**
     * @throws UnknownCampaign when the account has no campaign with this id
     */
    public function get(AccountId $accountId, CampaignId $id): CampaignSheet;

    /** Null when the account has no campaign with this id. */
    public function find(AccountId $accountId, CampaignId $id): ?CampaignSheet;

    /**
     * @param list<CampaignId> $ids
     *
     * @return array<string, string> the name of each of these campaigns that the account has, by id
     */
    public function namesOf(AccountId $accountId, array $ids): array;

    /**
     * @return list<CampaignSheet> every campaign of the account, newest first (ADR 0144)
     */
    public function ofAccount(AccountId $accountId): array;
}
