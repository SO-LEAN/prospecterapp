<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * Only a started campaign is run, one recipient after another. A campaign still in preparation, stopped or ended is not
 * run.
 */
final class CampaignNotRunning extends NotPossibleInItsState
{
    public static function toRun(): self
    {
        return self::because('Only a started campaign can be run');
    }
}
