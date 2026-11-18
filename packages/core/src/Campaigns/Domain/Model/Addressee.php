<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * What a message names a contact by: their name, their civility as PersonalData publishes it (ms, mr, x while
 * unknown), their first organization and their job title, each unknown or not.
 */
final readonly class Addressee
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public string $civility,
        public ?string $organization,
        public ?string $jobTitle,
    ) {
    }

    /**
     * Their first name and their last name, those known.
     */
    #[\NoDiscard]
    public function name(): string
    {
        return trim(sprintf('%s %s', $this->firstName, $this->lastName));
    }

    /**
     * How a message addresses them, Madame or Monsieur, nothing while their civility is unknown.
     */
    #[\NoDiscard]
    public function civilityTitle(): string
    {
        return match ($this->civility) {
            'ms' => 'Madame',
            'mr' => 'Monsieur',
            default => '',
        };
    }
}
