<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * What a member may do with a campaign, as its state allows.
 */
enum CampaignIntent
{
    /** Change its name and its message, its channel and its tags too while a draft. */
    case ReviseCampaign;
    /** Discard it, never started. */
    case DiscardCampaign;
    /** Keep the contacts its search finds now as its recipients. */
    case FreezeCampaign;
    /** Add recipients, found by tags or chosen. */
    case AddRecipients;
    /** Take a recipient off. */
    case RemoveRecipient;
}
