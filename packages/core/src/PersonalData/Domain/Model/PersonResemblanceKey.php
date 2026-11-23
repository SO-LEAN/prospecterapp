<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\SearchKey;

/**
 * What two contacts who resemble each other share. They may be the same person, logged twice.
 */
final readonly class PersonResemblanceKey implements \Stringable
{
    private function __construct(public string $value)
    {
    }

    /**
     * Their first name and last name, without case, accents or spaces. It is null without both names, because a last
     * name alone resembles too many people.
     */
    #[\NoDiscard]
    public static function of(PersonName $name): ?self
    {
        $firstName = SearchKey::of($name->firstName);
        $lastName = SearchKey::of($name->lastName);

        return null === $firstName || null === $lastName ? null : new self(sprintf('%s#%s', $firstName, $lastName));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
