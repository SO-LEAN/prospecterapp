<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * Where a campaign is in its life (ADR 0144): prepared as a draft, whose target follows the tags of the contacts, then
 * frozen, its recipients kept and changed by hand only.
 */
enum CampaignStatus: string
{
    case Draft = 'draft';
    case Frozen = 'frozen';

    /**
     * Whom it targets is the search by its tags as the contacts carry them now, nobody kept.
     */
    #[\NoDiscard]
    public function followsTheTags(): bool
    {
        return self::Draft === $this;
    }
}
