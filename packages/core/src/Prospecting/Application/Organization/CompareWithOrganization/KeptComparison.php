<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Organization\CompareWithOrganization;

use Prospecter\Core\SharedKernel\Application\Response\Comparison;

/**
 * Keeps the answer unchanged, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptComparison implements CompareWithOrganizationOutput
{
    public private(set) Comparison $comparison;

    public function present(Comparison $comparison): void
    {
        $this->comparison = $comparison;
    }
}
