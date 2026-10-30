<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptTimeline implements TimelineOutput
{
    public private(set) Timeline $timeline;

    public function present(Timeline $timeline): void
    {
        $this->timeline = $timeline;
    }
}
