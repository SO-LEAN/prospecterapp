<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Two requests noted the profile on two contacts at once: the second one, refused by the safety net of
 * LinkedInProfileUniqueness, does not know who holds it.
 */
final class LinkedInProfileTakenMeanwhile extends BusinessRuleViolation
{
    public static function byAnother(?\Throwable $detectedBy = null): self
    {
        return self::because('LinkedIn profile noted meanwhile on another contact', 'linkedInProfile', $detectedBy);
    }
}
