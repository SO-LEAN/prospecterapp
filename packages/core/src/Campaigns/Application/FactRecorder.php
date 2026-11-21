<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application;

use Prospecter\Core\Campaigns\Domain\Model\Fact;
use Prospecter\Core\Campaigns\Domain\Policy\FactSuccession;
use Prospecter\Core\Campaigns\Domain\Repository\FactRepository;

/**
 * Keeps a fact received, by the worker or by a rebuild, in place of the known one it succeeds (ADR 0141); received twice
 * by the worker, kept once, and rebuilt at the same revision, kept again.
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
