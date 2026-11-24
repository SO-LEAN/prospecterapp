<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Timeline\ConsultTimelineIntents;

/**
 * Keeps the answer unchanged, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptTimelineIntents implements ConsultTimelineIntentsOutput
{
    public private(set) TimelineIntentsResponse $intents;

    public function present(TimelineIntentsResponse $intents): void
    {
        $this->intents = $intents;
    }
}
