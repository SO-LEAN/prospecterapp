<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application;

use Prospecter\Core\Campaigns\Domain\Model\Fact;
use Prospecter\Core\Campaigns\Domain\Policy\FactSuccession;
use Prospecter\Core\Campaigns\Domain\Repository\FactRepository;

/**
 * Keeps a fact received, by the worker or by a rebuild alike, in place of the known one it succeeds (ADR 0141);
 * received twice, kept once.
 */
final readonly class FactRecorder
{
    public function __construct(private FactRepository $facts)
    {
    }

    public function record(Fact $received): void
    {
        $known = $this->facts->find($received);

        if (FactSuccession::replaces($received, $known)) {
            $this->facts->record($received, $known);
        }
    }
}
