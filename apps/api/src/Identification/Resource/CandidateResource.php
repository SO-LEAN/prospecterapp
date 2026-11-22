<?php

declare(strict_types=1);

namespace App\Identification\Resource;

/**
 * A contact or an organization of the account the clues may designate: its id, to read it, and its name.
 */
final readonly class CandidateResource
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }
}
