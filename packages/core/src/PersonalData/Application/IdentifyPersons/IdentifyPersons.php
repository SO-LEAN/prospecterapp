<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\IdentifyPersons;

/**
 * Which contacts of the account the clues designate, each person as the client sees them: known, new, or to confirm
 * among candidates; nothing written.
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
