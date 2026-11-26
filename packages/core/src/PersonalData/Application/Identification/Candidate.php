<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Identification;

/**
 * One of the account's contacts that the clues may designate. It carries the name every member of the account reads.
 */
final readonly class Candidate
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }
}
