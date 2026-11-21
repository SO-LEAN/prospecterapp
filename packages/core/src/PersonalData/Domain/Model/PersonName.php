<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

/**
 * The first name and the last name of a person, as they are written: without the spaces at their ends, one space
 * between their words, absent when nothing but spaces.
 */
final readonly class PersonName
{
    public ?string $firstName;
    public ?string $lastName;

    public function __construct(?string $firstName = null, ?string $lastName = null)
    {
        $this->firstName = self::tidy($firstName);
        $this->lastName = self::tidy($lastName);
    }

    private static function tidy(?string $part): ?string
    {
        $tidy = trim((string) preg_replace('/\s+/', ' ', (string) $part));

        return '' === $tidy ? null : $tidy;
    }
}
