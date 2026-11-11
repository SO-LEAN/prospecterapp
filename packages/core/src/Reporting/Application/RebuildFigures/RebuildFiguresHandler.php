<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\RebuildFigures;

use Prospecter\Core\Reporting\Application\FactRecorder;
use Prospecter\Core\Reporting\Application\Port\ProjectionVersions;
use Prospecter\Core\Reporting\Application\Port\RecordingsInFlight;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\UnitOfWork;

/**
 * Counts the figures again in a new version, out of the sight of the members until it is complete, then makes it the
 * active one. Each step is a unit of work of its own: the new version is seen by the recordings before the facts are
 * read again, and a rebuild writes far more than one unit holds. The delivery calls it without
 * TransactionalCommandHandler (ADR 0103).
 */
final readonly class RebuildFiguresHandler implements CommandHandler
{
    private const int FIRST_VERSION = 1;

    public function __construct(
        private ProjectionVersions $versions,
        private RecordingsInFlight $recordingsInFlight,
        private SourceFacts $sourceFacts,
        private FactRecorder $recorder,
        private UnitOfWork $unitOfWork,
    ) {
    }

    public function __invoke(RebuildFigures $command): void
    {
        $rebuilt = $this->nextVersion();
        $this->inItsOwnUnit(fn () => $this->versions->startBuilding($rebuilt));
        $this->recordingsInFlight->awaitTheirEnd();

        foreach ($this->sourceFacts->all() as $fact) {
            $this->inItsOwnUnit(fn () => $this->recorder->recordIn($rebuilt, $fact));
        }

        $this->inItsOwnUnit(fn () => $this->versions->activate($rebuilt));
    }

    private function nextVersion(): int
    {
        $active = $this->versions->active();

        return null === $active ? self::FIRST_VERSION : $active + 1;
    }

    /**
     * @param \Closure(): void $step
     */
    private function inItsOwnUnit(\Closure $step): void
    {
        $this->unitOfWork->begin();

        try {
            $step();
            $this->unitOfWork->commit();
        } finally {
            $this->unitOfWork->discard();
        }
    }
}
