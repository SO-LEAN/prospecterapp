<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Two requests noted the link on two contacts at once: the second one, refused by the safety net of
 * ReferenceUniqueness, does not know who holds it.
 */
final class ReferenceTakenMeanwhile extends BusinessRuleViolation
{
    public static function byAnother(?\Throwable $detectedBy = null): self
    {
        return self::because('Link noted meanwhile on another contact', 'links', $detectedBy);
    }
}
