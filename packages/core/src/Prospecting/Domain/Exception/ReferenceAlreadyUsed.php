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
        return self::because(sprintf('Link already the one of %s', $organization), 'links', context: ['holder' => $organization]);
    }

    public static function byAnother(?\Throwable $detectedBy = null): self
    {
        return self::because('Link already the one of another organization', 'links', $detectedBy);
    }
}
