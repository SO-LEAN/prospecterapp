<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Tagging;

use Prospecter\Core\Campaigns\Domain\Model\Fact;
use Prospecter\Core\Campaigns\Domain\Repository\FactRepository;
use Prospecter\Core\Campaigns\Domain\Rule\FactSuccession;

/**
 * Keeps a received fact in place of the known fact it succeeds (ADR 0141). The worker or a rebuild receives it. A fact
 * that the worker receives twice is kept once. A fact rebuilt at the same revision is kept again.
 */
final readonly class FactRecorder
{
    public function __construct(private FactRepository $facts)
    {
    }

    public function record(Fact $received): void
    {
        $this->keep($received, FactSuccession::replaces(...));
    }

    public function recordAgain(Fact $rebuilt): void
    {
        $this->keep($rebuilt, FactSuccession::replacesAsRebuilt(...));
    }

    /**
     * @param \Closure(Fact, ?Fact): bool $replaces
     */
    private function keep(Fact $received, \Closure $replaces): void
    {
        $known = $this->facts->find($received);

        if ($replaces($received, $known)) {
            $this->facts->record($received, $known);
        }
    }
}
