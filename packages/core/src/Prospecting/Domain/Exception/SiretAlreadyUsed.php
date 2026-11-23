<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Another organization of the account already has the SIRET. An establishment is one organization.
 */
final class SiretAlreadyUsed extends BusinessRuleViolation
{
    public static function by(string $organization): self
    {
        return self::because(sprintf('SIRET already the one of %s', $organization), 'siret', context: ['organization' => $organization]);
    }

    public static function byAnother(?\Throwable $detectedBy = null): self
    {
        return self::because('SIRET already the one of another organization', 'siret', $detectedBy);
    }
}
