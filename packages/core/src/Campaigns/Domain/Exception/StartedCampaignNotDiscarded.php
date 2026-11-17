<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * A campaign that started stays visible to the team: it is stopped, never discarded by a member.
 */
final class StartedCampaignNotDiscarded extends NotPossibleInItsState
{
    public static function stoppedInstead(): self
    {
        return self::because('A started campaign is stopped, not deleted');
    }
}
