<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * Where a campaign is in its life (ADR 0144). A member prepares it as a draft, whose target follows the contacts' tags.
 * Then the campaign is frozen: it keeps its recipients, and only a member changes them by hand. Then it is started, and
 * it runs as it was frozen, one recipient after another. Last, it is stopped, or it ends once nobody is left to handle
 * (ADR 0145).
 */
enum CampaignStatus: string
{
    case Draft = 'draft';
    case Frozen = 'frozen';
    case Started = 'started';
    case Stopped = 'stopped';
    case Ended = 'ended';

    /** It targets the contacts that its tag search finds now. It keeps nobody. */
    #[\NoDiscard]
    public function followsTheTags(): bool
    {
        return self::Draft === $this;
    }

    /** It runs or has run. It no longer changes, and it keeps every recipient it had. */
    #[\NoDiscard]
    public function hasStarted(): bool
    {
        return in_array($this, [self::Started, self::Stopped, self::Ended], true);
    }

    /** It runs now: its recipients are handled one after another. */
    #[\NoDiscard]
    public function isRunning(): bool
    {
        return self::Started === $this;
    }
}
