<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * Only a started campaign is led, recipient after recipient: neither one still prepared, nor one stopped or ended.
 */
final class CampaignNotRunning extends NotPossibleInItsState
{
    public static function toRun(): self
    {
        return self::because('Only a started campaign is run');
    }
}
