<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\IdentifyOrganizations;

/**
 * An organization of the account the clues may designate.
 */
final readonly class Candidate
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }
}
