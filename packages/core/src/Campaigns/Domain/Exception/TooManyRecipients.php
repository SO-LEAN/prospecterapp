<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/** A team contacts a few hundred people by hand in a campaign, not more. */
final class TooManyRecipients extends BusinessRuleViolation
{
    public static function beyond(int $max): self
    {
        return self::because(sprintf('A campaign can have at most %d recipients', $max), context: ['max' => (string) $max]);
    }
}
