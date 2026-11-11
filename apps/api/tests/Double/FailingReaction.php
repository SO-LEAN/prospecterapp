<?php

namespace Tests\App\Double;

use Prospecter\Core\Prospecting\Domain\Event\ObservationsWritten;
use Prospecter\Core\SharedKernel\Application\EventHandler;

/**
 * A reaction to written observations, a subscription of the tests (`prospecting-failing-reaction`), that throws
 * once a test asks it to, the times it says or until it stops; it does nothing otherwise. It counts its calls.
 */
final class FailingReaction implements EventHandler
{
    private ?\Throwable $failure = null;
    private int $failuresLeft = 0;
    public private(set) int $calls = 0;

    public function failWith(\Throwable $failure, int $times = \PHP_INT_MAX): void
    {
        $this->failure = $failure;
        $this->failuresLeft = $times;
    }

    public function stopFailing(): void
    {
        $this->failure = null;
    }

    public function __invoke(ObservationsWritten $event): void
    {
        ++$this->calls;

        if (null !== $this->failure && $this->failuresLeft-- > 0) {
            throw $this->failure;
        }
    }
}
