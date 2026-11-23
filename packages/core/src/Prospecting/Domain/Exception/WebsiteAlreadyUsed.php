<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Another organization of the account already has a website on the same domain. A domain designates one organization.
 */
final class WebsiteAlreadyUsed extends BusinessRuleViolation
{
    public static function by(string $organization): self
    {
        return self::because(sprintf('%s already has this website', $organization), 'website', context: ['organization' => $organization]);
    }

    public static function byAnother(?\Throwable $detectedBy = null): self
    {
        return self::because('Another organization already has this website', 'website', $detectedBy);
    }
}
