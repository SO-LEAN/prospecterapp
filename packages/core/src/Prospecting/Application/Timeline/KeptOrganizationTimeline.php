<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Timeline;

/**
 * Keeps the answer as it is, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptOrganizationTimeline implements OrganizationTimelineOutput
{
    public private(set) ?OrganizationTimeline $timeline = null;

    public function present(OrganizationTimeline $timeline): void
    {
        $this->timeline = $timeline;
    }
}
