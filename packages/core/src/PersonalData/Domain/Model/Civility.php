<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\PersonalData\Domain\Exception\InvalidTitle;

/**
 * How to address a contact when their first name does not tell. It is unknown until a member logs it. Every member
 * reads it unmasked, like the name (ADR 0129).
 */
enum Civility: string
{
    case Ms = 'ms';
    case Mr = 'mr';
    case X = 'x';

    /**
     * @throws InvalidTitle
     */
    public static function of(string $value): self
    {
        return self::tryFrom($value) ?? throw InvalidTitle::of($value);
    }
}
