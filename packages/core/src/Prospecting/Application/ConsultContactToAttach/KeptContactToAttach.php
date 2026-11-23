<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultContactToAttach;

use Prospecter\Core\Prospecting\Application\ContactToAttachInList;

/**
 * Keeps the answer unchanged, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptContactToAttach implements ConsultContactToAttachOutput
{
    public private(set) ?ContactToAttachInList $contactToAttach = null;

    public function present(?ContactToAttachInList $contactToAttach): void
    {
        $this->contactToAttach = $contactToAttach;
    }
}
