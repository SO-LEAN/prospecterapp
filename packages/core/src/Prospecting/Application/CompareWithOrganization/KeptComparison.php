<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\CompareWithOrganization;

use Prospecter\Core\SharedKernel\Application\Comparison;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptComparison implements CompareWithOrganizationOutput
{
    public private(set) Comparison $comparison;

    public function present(Comparison $comparison): void
    {
        $this->comparison = $comparison;
    }
}
