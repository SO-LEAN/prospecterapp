<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\PersonalData\Domain\Exception\InvalidCivility;

/**
 * How one addresses a contact, when their first name does not tell: unknown until noted, read in clear like their
 * name (ADR 0129).
 */
enum Civility: string
{
    case Ms = 'ms';
    case Mr = 'mr';
    case X = 'x';

    /**
     * @throws InvalidCivility
     */
    public static function of(string $value): self
    {
        return self::tryFrom($value) ?? throw InvalidCivility::of($value);
    }
}
