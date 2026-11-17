<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * A campaign starts once its recipients are frozen, and only once: a draft, a started or a stopped campaign does not.
 */
final class CampaignNotFrozen extends NotPossibleInItsState
{
    public static function toStart(): self
    {
        return self::because('Only a frozen campaign starts');
    }
}
