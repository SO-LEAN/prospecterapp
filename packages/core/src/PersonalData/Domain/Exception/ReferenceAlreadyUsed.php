<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Exception;

use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Another contact in the account already holds the link, as a current or a former link.
 */
final class ReferenceAlreadyUsed extends BusinessRuleViolation
{
    /**
     * The message names the holder to the member who logs the link. That member reads the name of every contact in
     * their account (ADR 0129).
     */
    public static function heldBy(Person $holder): self
    {
        $name = trim(sprintf('%s %s', $holder->name()->firstName, $holder->name()->lastName));

        return self::because(sprintf('%s already has this link', $name), 'links', context: ['holder' => $name, 'holderId' => $holder->id()->value]);
    }
}
