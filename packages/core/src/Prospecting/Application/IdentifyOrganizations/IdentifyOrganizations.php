<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\IdentifyOrganizations;

/**
 * Which organizations of the account the clues designate, each organization as the client sees it: known, new, or
 * to confirm among candidates; nothing written.
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
