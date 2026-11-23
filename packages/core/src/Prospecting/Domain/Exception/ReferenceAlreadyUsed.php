<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Another organization of the account already has the link. A reference designates one organization.
 */
final class ReferenceAlreadyUsed extends BusinessRuleViolation
{
    public static function by(string $organization): self
    {
        return self::because(sprintf('%s already has this link', $organization), 'links', context: ['holder' => $organization]);
    }

    public static function byAnother(?\Throwable $detectedBy = null): self
    {
        return self::because('Another organization already has this link', 'links', $detectedBy);
    }
}
