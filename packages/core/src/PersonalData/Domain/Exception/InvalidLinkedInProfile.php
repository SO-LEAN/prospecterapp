<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The address given as a LinkedIn profile is no profile of a person on LinkedIn.
 */
final class InvalidLinkedInProfile extends BusinessRuleViolation
{
    public static function of(string $value): self
    {
        return self::because(sprintf('LinkedIn profile "%s" is not valid', $value), 'linkedInProfile', context: ['linkedInProfile' => $value]);
    }
}
