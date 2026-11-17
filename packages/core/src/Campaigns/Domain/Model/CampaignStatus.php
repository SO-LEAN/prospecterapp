<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * Where a campaign is in its life (ADR 0144): prepared as a draft, whose target follows the tags of the contacts.
 */
enum CampaignStatus: string
{
    case Draft = 'draft';
}
