<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The name of a campaign is read in a list: too long, it is refused.
 */
final class CampaignNameTooLong extends BusinessRuleViolation
{
    public static function longerThan(int $max): self
    {
        return self::because(sprintf('A campaign name has %d characters at most', $max), 'name', context: ['max' => (string) $max]);
    }
}
