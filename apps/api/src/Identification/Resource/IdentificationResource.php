<?php

declare(strict_types=1);

namespace App\Identification\Resource;

/**
 * What a person's or an organization's clues designate: `known` (one candidate), `new` (none) or `toConfirm`
 * (several candidates, for the member to choose from).
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
