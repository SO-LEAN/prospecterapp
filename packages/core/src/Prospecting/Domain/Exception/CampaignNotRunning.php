<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * An exchange is noted in a campaign only while it is led: neither prepared, nor stopped, nor ended.
 */
final class CampaignNotRunning extends NotPossibleInItsState
{
    public static function toRun(): self
    {
        return self::because('Only a started campaign is run');
    }
}
