<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts;

/**
 * The facts of an organization, what another context counts of it: whether the account prospects it, whether it
 * is placed on the map, and the revision of its state, incremented at each change.
 */
final readonly class OrganizationFactsResponse
{
    private const int NO_REVISION = 0;

    public function __construct(
        public string $organizationId,
        public int $revision,
        public bool $present,
        public bool $located,
    ) {
    }

    /**
     * An organization the account no longer prospects: the last of its facts.
     */
    public static function absent(string $organizationId): self
    {
        return new self($organizationId, self::NO_REVISION, false, false);
    }
}
