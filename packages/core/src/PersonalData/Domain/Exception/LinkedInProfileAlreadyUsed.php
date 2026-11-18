<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The LinkedIn profile is already another contact's of the account, as their own or a former one.
 */
final class LinkedInProfileAlreadyUsed extends BusinessRuleViolation
{
    /**
     * Two requests took the profile at once: the second one does not know who holds it.
     */
    public static function byAnother(?\Throwable $detectedBy = null): self
    {
        return self::because('LinkedIn profile already held by another contact', 'linkedInProfile', $detectedBy);
    }
}
