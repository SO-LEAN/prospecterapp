<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain;

use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * Personal data shown to a reader without the right to read it in clear. A fixed number of stars, so that the
 * length of a name or of an address does not leak either.
 */
final class Masking
{
    private const string STARS = '***';

    public static function name(?string $name): ?string
    {
        if (null === $name || '' === $name) {
            return $name;
        }

        return mb_strlen($name) <= 2 ? self::STARS : mb_substr($name, 0, 1).self::STARS.mb_substr($name, -1);
    }

    public static function jobTitle(?string $jobTitle): ?string
    {
        return self::name($jobTitle);
    }

    public static function email(?string $email): ?string
    {
        return null === $email ? null : mb_substr($email, 0, 1).self::STARS.'@'.self::STARS;
    }

    public static function phoneNumber(?PhoneNumber $phoneNumber): ?string
    {
        return null === $phoneNumber ? null : self::STARS.self::STARS.mb_substr($phoneNumber->value, -2);
    }
}
