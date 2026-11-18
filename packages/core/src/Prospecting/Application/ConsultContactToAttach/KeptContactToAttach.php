<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultContactToAttach;

use Prospecter\Core\Prospecting\Application\ContactToAttachInList;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptContactToAttach implements ConsultContactToAttachOutput
{
    public private(set) ?ContactToAttachInList $contactToAttach = null;

    public function present(?ContactToAttachInList $contactToAttach): void
    {
        $this->contactToAttach = $contactToAttach;
    }
}
