<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * A member logs an exchange in a campaign only while the campaign runs. A campaign in preparation, stopped or ended
 * takes no exchange.
 */
final class CampaignNotRunning extends NotPossibleInItsState
{
    public static function toRun(): self
    {
        return self::because('Only a started campaign can be run');
    }
}
