<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application;

use Prospecter\Core\Reporting\Application\Port\ProjectionVersions;
use Prospecter\Core\Reporting\Domain\Model\Fact;
use Prospecter\Core\Reporting\Domain\Policy\FactSuccession;
use Prospecter\Core\Reporting\Domain\Policy\FiguresDelta;
use Prospecter\Core\Reporting\Domain\Repository\FactRepository;

/**
 * Applies a fact to the figures. When the fact replaces its aggregate's known fact, the recorder keeps it with the
 * change it makes to the figures. Otherwise the recorder ignores it. The same code records a received fact and a fact
 * that a rebuild reads again.
 */
final readonly class FactRecorder
{
    public function __construct(private FactRepository $facts, private ProjectionVersions $versions)
    {
    }

    /**
     * Records the fact in every live version, including the one being built. So a rebuild misses no fact received while
     * it runs.
     */
    public function record(Fact $received): void
    {
        foreach ($this->versions->live() as $version) {
            $this->recordIn($version, $received);
        }
    }

    public function recordIn(int $version, Fact $received): void
    {
        $known = $this->facts->find($version, $received);

        if (FactSuccession::replaces($received, $known)) {
            $this->facts->record($version, $received, $known, FiguresDelta::between($known, $received));
        }
    }
}
