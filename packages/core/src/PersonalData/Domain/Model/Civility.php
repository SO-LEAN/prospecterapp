<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\PersonalData\Domain\Exception\InvalidCivility;

/**
 * How one addresses a contact, when their first name does not tell: never required, read in clear like their name
 * (ADR 0129).
 */
enum Civility: string
{
    case Ms = 'ms';
    case Mr = 'mr';

    /**
     * @throws InvalidCivility
     */
    public static function orNone(?string $value): ?self
    {
        return null === $value ? null : self::tryFrom($value) ?? throw InvalidCivility::of($value);
    }
}
