<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/** A started campaign runs as it was frozen. Its recipients, message and channel no longer change. */
final class StartedCampaign extends NotPossibleInItsState
{
    public static function unchanged(): self
    {
        return self::because('A started campaign can no longer change');
    }
}
