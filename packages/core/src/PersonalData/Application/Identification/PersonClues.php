<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Identification;

/**
 * A person as the client sees them: what the client knows about them. Each clue is optional.
 */
final readonly class PersonClues
{
    /**
     * @param list<string> $links
     * @param list<string> $emails
     * @param list<string> $phoneNumbers
     * @param list<string> $organizationIds the account's organizations they may work for
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
