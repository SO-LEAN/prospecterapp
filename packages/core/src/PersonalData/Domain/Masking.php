<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain;

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

    public static function email(?string $email): ?string
    {
        return null === $email ? null : mb_substr($email, 0, 1).self::STARS.'@'.self::STARS;
    }

    public static function phoneNumber(?string $phoneNumber): ?string
    {
        if (null === $phoneNumber) {
            return null;
        }

        $digits = (string) preg_replace('/\D/', '', $phoneNumber);

        return self::STARS.self::STARS.mb_substr($digits, -2);
    }
}
