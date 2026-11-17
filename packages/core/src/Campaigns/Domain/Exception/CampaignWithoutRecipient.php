<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A frozen campaign keeps someone to contact: it is not frozen empty, its last recipient is not taken off.
 */
final class CampaignWithoutRecipient extends BusinessRuleViolation
{
    public static function left(): self
    {
        return self::because('A campaign has at least one recipient');
    }
}
