<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Rule;

use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReferences;

/**
 * Personal data shown to a reader without the right to read it unmasked. It is a fixed number of stars, so that the
 * length of a name or an address does not leak.
 */
final class Masking
{
    private const string STARS = '***';

    #[\NoDiscard]
    public static function name(?string $name): ?string
    {
        if (null === $name || '' === $name) {
            return $name;
        }

        return mb_strlen($name) <= 2 ? self::STARS : mb_substr($name, 0, 1).self::STARS.mb_substr($name, -1);
    }

    #[\NoDiscard]
    public static function jobTitle(?string $jobTitle): ?string
    {
        return self::name($jobTitle);
    }

    #[\NoDiscard]
    public static function email(?string $email): ?string
    {
        return null === $email ? null : mb_substr($email, 0, 1).self::STARS.'@'.self::STARS;
    }

    /**
     * One link of stars for any number of links, and none when there is no link. Neither their number nor their sources
     * leak.
     *
     * @return list<string>
     */
    #[\NoDiscard]
    public static function references(ExternalReferences $references): array
    {
        return [] === $references->all() ? [] : [self::STARS];
    }

    #[\NoDiscard]
    public static function phoneNumber(?PhoneNumber $phoneNumber): ?string
    {
        return null === $phoneNumber ? null : self::STARS.self::STARS.mb_substr($phoneNumber->value, -2);
    }
}
