<?php

declare(strict_types=1);

namespace App\Reporting;

use Prospecter\Core\Reporting\Application\Port\RecordingsInFlight;

/**
 * For a command that is the only one to write, such as the restore of the demonstration: Reporting's table has just
 * been emptied, so no version is live, and a recording begun before records nothing (ADR 0209).
 */
final readonly class NoRecordingsInFlight implements RecordingsInFlight
{
    public function awaitTheirEnd(): void
    {
    }
}
