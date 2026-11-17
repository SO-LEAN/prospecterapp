<?php

namespace App\Campaigning;

use App\Security\AccountResourceVoter;
use Prospecter\Core\Campaigns\Application\CampaignSheet;

/**
 * Whether the signed-in member reaches a campaign: one of their account (ADR 0081, 0144).
 *
 * @extends AccountResourceVoter<CampaignSheet>
 */
final class CampaignVoter extends AccountResourceVoter
{
    public const string ACCESS = 'CAMPAIGN_ACCESS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ACCESS === $attribute && $subject instanceof CampaignSheet;
    }

    protected function accountOf(object $subject): string
    {
        return $subject->accountId;
    }

    protected function unknownReason(): string
    {
        return 'unknownCampaign';
    }
}
