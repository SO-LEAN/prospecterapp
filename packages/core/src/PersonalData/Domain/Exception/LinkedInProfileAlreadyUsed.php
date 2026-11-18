<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Exception;

use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The LinkedIn profile is already another contact's of the account, as their own or a former one.
 */
final class LinkedInProfileAlreadyUsed extends BusinessRuleViolation
{
    /**
     * Named to the member noting it, who reads the name of every contact of their account (ADR 0129).
     */
    public static function heldBy(Person $holder): self
    {
        $name = trim(sprintf('%s %s', $holder->name()->firstName, $holder->name()->lastName));

        return self::because(sprintf('LinkedIn profile already held by %s', $name), 'linkedInProfile', context: ['holder' => $name, 'holderId' => $holder->id()->value]);
    }
}
