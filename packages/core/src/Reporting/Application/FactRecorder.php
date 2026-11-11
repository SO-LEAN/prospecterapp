<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application;

use Prospecter\Core\Reporting\Application\Port\ProjectionVersions;
use Prospecter\Core\Reporting\Domain\Model\Fact;
use Prospecter\Core\Reporting\Domain\Policy\FactSuccession;
use Prospecter\Core\Reporting\Domain\Policy\FiguresDelta;
use Prospecter\Core\Reporting\Domain\Repository\FactRepository;

/**
 * Applies a fact to the figures: kept, with what it changes, when it takes the place of the one known of its
 * aggregate; ignored otherwise. The same code for a fact received and for a fact read again by a rebuild.
 */
final readonly class FactRecorder
{
    public function __construct(private FactRepository $facts, private ProjectionVersions $versions)
    {
    }

    /**
     * In every live version, the one being built included, so that a rebuild misses no fact received meanwhile.
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
