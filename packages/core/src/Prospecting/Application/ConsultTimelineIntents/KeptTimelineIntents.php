<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultTimelineIntents;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0084).
 */
final class KeptTimelineIntents implements ConsultTimelineIntentsOutput
{
    public private(set) TimelineIntentsResponse $intents;

    public function present(TimelineIntentsResponse $intents): void
    {
        $this->intents = $intents;
    }
}
