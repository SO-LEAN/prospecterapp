<?php

namespace Tests\App\Double;

use Prospecter\Core\Prospecting\Domain\Event\ObservationsWritten;
use Prospecter\Core\SharedKernel\Application\EventHandler;

/**
 * A reaction to written observations, at the place of a translator publishing them, that throws once a test asks
 * it to; it does nothing otherwise.
 */
final class FailingReaction implements EventHandler
{
    private ?\Throwable $failure = null;

    public function failWith(\Throwable $failure): void
    {
        $this->failure = $failure;
    }

    public function __invoke(ObservationsWritten $event): void
    {
        if (null !== $this->failure) {
            throw $this->failure;
        }
    }
}
