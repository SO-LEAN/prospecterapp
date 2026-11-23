<?php

declare(strict_types=1);

namespace App\Reporting;

use Prospecter\Core\Reporting\Application\Port\RecordingsInFlight;
use Symfony\Component\Clock\ClockInterface;

/**
 * The worker records the facts. It runs for 120 seconds at most (`infra/messaging.tf`, ADR 0182). So after two and a
 * half minutes, every recording begun before has ended, whether written or abandoned (ADR 0103, 0104).
 */
final readonly class RecordingsOfTheWorker implements RecordingsInFlight
{
    private const int LONGER_THAN_THE_WORKER = 150;

    public function __construct(private ClockInterface $clock)
    {
    }

    public function awaitTheirEnd(): void
    {
        $this->clock->sleep(self::LONGER_THAN_THE_WORKER);
    }
}
