<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

/**
 * A person's first name and last name, as they are written. The spaces at their ends are removed, and one space
 * separates their words. A part made of nothing but spaces is absent.
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
