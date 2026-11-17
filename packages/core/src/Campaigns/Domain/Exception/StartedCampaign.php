<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * Once started, a campaign is led as it was frozen: neither its recipients, nor its message, nor its channel change.
 */
final class StartedCampaign extends NotPossibleInItsState
{
    public static function unchanged(): self
    {
        return self::because('A started campaign does not change');
    }
}
