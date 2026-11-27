<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Identification\IdentifyOrganizations;

/**
 * Finds which of the account's organizations the clues designate. Each set of clues is an organization as the client
 * sees it. The answer is known, new, or to confirm among candidates. The query writes nothing.
 */
final readonly class IdentifyOrganizations
{
    /**
     * @param list<OrganizationClues> $clues
     */
    public function __construct(
        public string $prospectorId,
        public array $clues,
    ) {
    }
}
