<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Two requests logged the link on two contacts at the same time. ReferenceUniqueness's safety net refuses the second
 * request. The refusal does not know who holds the link.
 */
final class ReferenceTakenMeanwhile extends BusinessRuleViolation
{
    public static function byAnother(?\Throwable $detectedBy = null): self
    {
        return self::because('Another contact took this link in the meantime', 'links', $detectedBy);
    }
}
