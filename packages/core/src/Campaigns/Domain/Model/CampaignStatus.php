<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * Where a campaign is in its life (ADR 0144): prepared as a draft, whose target follows the tags of the contacts, then
 * frozen, its recipients kept and changed by hand only, then started, led as it was frozen, recipient after recipient,
 * and stopped, or ended once nobody is left to handle (ADR 0145).
 */
enum CampaignStatus: string
{
    case Draft = 'draft';
    case Frozen = 'frozen';
    case Started = 'started';
    case Stopped = 'stopped';
    case Ended = 'ended';

    /**
     * Whom it targets is the search by its tags as the contacts carry them now, nobody kept.
     */
    #[\NoDiscard]
    public function followsTheTags(): bool
    {
        return self::Draft === $this;
    }

    /**
     * Led, or led once: it no longer changes, and keeps every recipient it had.
     */
    #[\NoDiscard]
    public function hasStarted(): bool
    {
        return in_array($this, [self::Started, self::Stopped, self::Ended], true);
    }

    /**
     * Led now: its recipients are handled one after the other.
     */
    #[\NoDiscard]
    public function isRunning(): bool
    {
        return self::Started === $this;
    }
}
