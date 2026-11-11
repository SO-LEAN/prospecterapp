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
 * active one. It first abandons what a former rebuild left: the version that one was building is no longer live, then,
 * once the recordings that saw it live ended, every version but the active one is removed, so that no recording
 * writes in a version while it is emptied. Each step is a unit of work of its own: the new version is seen by the
 * recordings before the facts are read again, and a rebuild writes far more than one unit holds. The delivery calls
 * it without TransactionalCommandHandler (ADR 0103, 0106).
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
        $this->abandonWhatAFormerRebuildLeft();
        $this->startBuilding($rebuilt);

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

    private function abandonWhatAFormerRebuildLeft(): void
    {
        $this->inItsOwnUnit(fn () => $this->versions->abandonBuilding());
        $this->recordingsInFlight->awaitTheirEnd();
        $this->inItsOwnUnit(fn () => $this->versions->removeAbandoned());
    }

    private function startBuilding(int $version): void
    {
        $this->inItsOwnUnit(fn () => $this->versions->startBuilding($version));
        $this->recordingsInFlight->awaitTheirEnd();
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
