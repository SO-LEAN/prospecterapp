<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptProspected implements WhichAreProspectedOutput
{
    /**
     * @var list<string>
     */
    public private(set) array $organizationIds = [];

    /**
     * @param list<string> $organizationIds
     */
    public function present(array $organizationIds): void
    {
        $this->organizationIds = $organizationIds;
    }
}
