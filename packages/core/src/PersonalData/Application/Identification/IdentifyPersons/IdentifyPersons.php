<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Identification\IdentifyPersons;

/**
 * Finds which of the account's contacts the clues designate. The client gives each person as it sees them. Each
 * person is known, new, or to confirm among candidates. Nothing is written.
 */
final readonly class IdentifyPersons
{
    /**
     * @param list<PersonClues> $clues
     */
    public function __construct(
        public string $readerId,
        public array $clues,
    ) {
    }
}
