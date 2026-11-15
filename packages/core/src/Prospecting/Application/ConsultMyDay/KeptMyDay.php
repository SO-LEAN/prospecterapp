<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultMyDay;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptMyDay implements ConsultMyDayOutput
{
    public private(set) MyDay $myDay;

    public function present(MyDay $myDay): void
    {
        $this->myDay = $myDay;
    }
}
