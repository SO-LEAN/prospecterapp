<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Another organization of the account has the LinkedIn page already: one company page, one organization.
 */
final class LinkedInPageAlreadyUsed extends BusinessRuleViolation
{
    public static function by(string $organization): self
    {
        return self::because(sprintf('LinkedIn page already the one of %s', $organization), 'linkedInPage', context: ['organization' => $organization]);
    }

    public static function byAnother(?\Throwable $detectedBy = null): self
    {
        return self::because('LinkedIn page already the one of another organization', 'linkedInPage', $detectedBy);
    }
}
