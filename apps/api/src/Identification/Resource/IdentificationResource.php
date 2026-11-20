<?php

namespace App\Identification\Resource;

/**
 * What the clues of a person or an organization designate: `known` (its one candidate), `new` (none) or `toConfirm`
 * (its candidates, for the member to choose).
 */
final readonly class IdentificationResource
{
    /**
     * @param list<CandidateResource> $candidates
     */
    public function __construct(
        public string $status,
        public array $candidates,
    ) {
    }
}
