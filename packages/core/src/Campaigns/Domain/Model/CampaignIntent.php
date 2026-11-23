<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * What a member may do with a campaign, as its state allows.
 */
enum CampaignIntent
{
    /** Change its name and message. While it is a draft, also change its channel and tags. */
    case ReviseCampaign;
    /** Discard it before it ever starts. */
    case DiscardCampaign;
    /** Keep the contacts its search finds now as its recipients. */
    case FreezeCampaign;
    /** Add recipients, found by tags or chosen one by one. */
    case AddRecipients;
    /** Remove a recipient. */
    case RemoveRecipient;
    /** Start it, with its recipients and message as they were frozen. */
    case StartCampaign;
    /** Stop running it. The campaign stays for the team to read. */
    case StopCampaign;
    /** Run it: log a recipient's exchange, or dismiss them. */
    case RunCampaign;
    /** Set the most recipients processed in a day, or take that cap off. */
    case SetDailyCap;
}
