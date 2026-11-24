<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Agenda\ConsultMyDay;

/**
 * Keeps the answer unchanged, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptMyDay implements ConsultMyDayOutput
{
    public private(set) MyDay $myDay;

    public function present(MyDay $myDay): void
    {
        $this->myDay = $myDay;
    }
}
