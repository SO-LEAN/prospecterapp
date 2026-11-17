<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * What a member may do with a campaign, as its state allows.
 */
enum CampaignIntent
{
    /** Change its name, its channel, its tags and its message. */
    case ReviseCampaign;
    /** Discard it, never started. */
    case DiscardCampaign;
}
