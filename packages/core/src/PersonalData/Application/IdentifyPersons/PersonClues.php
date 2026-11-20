<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\IdentifyPersons;

/**
 * A person as the client sees them: what it knows of them, each clue optional.
 */
final readonly class PersonClues
{
    /**
     * @param list<string> $links
     * @param list<string> $emails
     * @param list<string> $phoneNumbers
     * @param list<string> $organizationIds the organizations of the account they may work for
     */
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public array $links = [],
        public array $emails = [],
        public array $phoneNumbers = [],
        public array $organizationIds = [],
    ) {
    }
}
