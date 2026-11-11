<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Reporting\Application\Port\RecordingsInFlight;

/**
 * The scenarios record each fact before the gesture that published it returns: none is in flight when a rebuild
 * starts.
 */
final readonly class NoRecordingsInFlight implements RecordingsInFlight
{
    public function awaitTheirEnd(): void
    {
    }
}
