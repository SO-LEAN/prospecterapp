<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/** A campaign that has started stays visible to the team. A member stops it, and never discards it. */
final class StartedCampaignNotDiscarded extends NotPossibleInItsState
{
    public static function stoppedInstead(): self
    {
        return self::because('A started campaign is stopped, not deleted');
    }
}
