<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * What a message calls a contact. It holds their name, their title as PersonalData publishes it (ms, mr, or x while
 * unknown), their first organization and their job title. Each of them may be unknown.
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

    /** Their first name and last name, as far as they are known. */
    #[\NoDiscard]
    public function name(): string
    {
        return trim(sprintf('%s %s', $this->firstName, $this->lastName));
    }

    /** How a message addresses them: Madame or Monsieur. It is empty while their title is unknown. */
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
